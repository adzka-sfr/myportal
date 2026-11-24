<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\HunianLog;
use App\Models\HunianMaintenance;
use App\Models\HunianOccupancy;
use App\Models\HunianRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Aktivitas harian: monitoring kamar, pergantian penghuni, pembayaran, perbaikan. */
class HunianController extends Controller
{
    public function index(): View
    {
        $rooms = HunianRoom::query()
            ->with(['facilities', 'activeOccupancy', 'reservation', 'openMaintenance'])
            ->orderBy('floor')->orderByRaw('LENGTH(room_number)')->orderBy('room_number')
            ->get();

        $byStatus = $rooms->groupBy(fn(HunianRoom $room) => $room->currentStatus());
        $occupied = $byStatus->get('occupied', collect());
        $stats = [
            'total' => $rooms->count(),
            'available' => $byStatus->get('available', collect())->count(),
            'occupied' => $occupied->count(),
            'reserved' => $byStatus->get('reserved', collect())->count(),
            'maintenance' => $byStatus->get('maintenance', collect())->count(),
            'overdue' => $occupied->filter(fn(HunianRoom $room) => $room->activeOccupancy->paymentState() === 'overdue')->count(),
            'income' => $occupied->sum(fn(HunianRoom $room) => $room->activeOccupancy->rent_price),
            'potential' => $byStatus->get('available', collect())->sum('monthly_price'),
        ];
        $stats['occupancy'] = $stats['total'] > 0 ? (int) round($stats['occupied'] / $stats['total'] * 100) : 0;

        return view('portal.kelola_hunian.index', [
            'floors' => $rooms->groupBy('floor'),
            'stats' => $stats,
            'statuses' => HunianRoom::STATUSES,
        ]);
    }

    public function occupy(Request $request, HunianRoom $room): RedirectResponse
    {
        if ($room->activeOccupancy()->exists() || $room->reservation()->exists() || $room->openMaintenance()->exists()) {
            return back()->withErrors(['room' => "Kamar {$room->room_number} sedang tidak kosong."]);
        }

        $data = $this->validatedOccupancy($request, $room->monthly_price);
        $occupancy = DB::transaction(function () use ($room, $data) {
            $occupancy = $room->occupancies()->create($data);
            $this->logOccupancy($occupancy, $room, $data['status'] === 'active' ? 'check_in' : 'reserved');

            return $occupancy;
        });

        return redirect()->route('apps.hunian')->with('status', $occupancy->status === 'active'
            ? "{$occupancy->occupant_name} check-in ke kamar {$room->room_number}."
            : "Kamar {$room->room_number} dipesan untuk {$occupancy->occupant_name}.");
    }

    public function updateOccupancy(Request $request, HunianOccupancy $occupancy): RedirectResponse
    {
        abort_if($occupancy->status === 'ended', 404);
        $room = $occupancy->room;
        $wasReserved = $occupancy->status === 'reserved';

        $data = $this->validatedOccupancy($request, $occupancy->rent_price, $occupancy->status);
        DB::transaction(function () use ($occupancy, $room, $data, $wasReserved) {
            $occupancy->update($data);
            $this->logOccupancy($occupancy, $room, $wasReserved && $data['status'] === 'active' ? 'check_in' : 'occupant_updated');
        });

        return redirect()->route('apps.hunian')->with('status', "Data kamar {$room->room_number} diperbarui.");
    }

    public function pay(Request $request, HunianOccupancy $occupancy): RedirectResponse
    {
        abort_unless($occupancy->status === 'active', 404);
        $data = $request->validate([
            'months' => ['required', 'integer', 'min:1', 'max:36'],
            'amount' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            '_dialog' => ['nullable', 'string'],
            '_target' => ['nullable', 'integer'],
        ], [], ['months' => 'jumlah bulan', 'amount' => 'nominal']);

        $room = $occupancy->room;
        $amount = (int) ($data['amount'] ?? $occupancy->rent_price * $data['months']);
        $base = $occupancy->paid_until ?? $occupancy->check_in_date ?? now();
        $newDate = $base->copy()->startOfDay()->addMonthsNoOverflow((int) $data['months']);

        DB::transaction(function () use ($occupancy, $room, $data, $amount, $newDate) {
            $occupancy->update(['paid_until' => $newDate]);
            HunianLog::record('payment', "Bayar sewa {$data['months']} bulan, terbayar sampai {$newDate->format('d M Y')}.", $room, $occupancy->occupant_name, $amount);
        });

        return redirect()->route('apps.hunian')->with('status', "Pembayaran kamar {$room->room_number} dicatat.");
    }

    public function end(HunianOccupancy $occupancy): RedirectResponse
    {
        abort_if($occupancy->status === 'ended', 404);
        $room = $occupancy->room;
        $wasReserved = $occupancy->status === 'reserved';

        DB::transaction(function () use ($occupancy, $room, $wasReserved) {
            $occupancy->update(['status' => 'ended', 'ended_at' => now()->toDateString()]);
            HunianLog::record(
                $wasReserved ? 'reservation_cancelled' : 'check_out',
                $wasReserved ? 'Booking dibatalkan.' : 'Penghuni keluar, kamar dikosongkan.',
                $room,
                $occupancy->occupant_name,
            );
        });

        return redirect()->route('apps.hunian')->with('status', $wasReserved ? 'Booking dibatalkan.' : "Kamar {$room->room_number} dikosongkan.");
    }

    public function startMaintenance(Request $request, HunianRoom $room): RedirectResponse
    {
        if ($room->activeOccupancy()->exists() || $room->openMaintenance()->exists()) {
            return back()->withErrors(['room' => "Kamar {$room->room_number} tidak dapat dimasukkan ke perbaikan."]);
        }
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            '_dialog' => ['nullable', 'string'],
            '_target' => ['nullable', 'integer'],
        ], [], ['reason' => 'keterangan perbaikan']);

        DB::transaction(function () use ($room, $data) {
            HunianMaintenance::create(['room_id' => $room->id, 'reason' => $data['reason'], 'started_at' => now()->toDateString()]);
            HunianLog::record('maintenance_started', "Perbaikan: {$data['reason']}", $room);
        });

        return redirect()->route('apps.hunian')->with('status', "Kamar {$room->room_number} masuk perbaikan.");
    }

    public function finishMaintenance(HunianMaintenance $maintenance): RedirectResponse
    {
        abort_if($maintenance->finished_at !== null, 404);
        $room = HunianRoom::findOrFail($maintenance->room_id);

        DB::transaction(function () use ($maintenance, $room) {
            $maintenance->update(['finished_at' => now()->toDateString()]);
            HunianLog::record('maintenance_finished', "Perbaikan selesai: {$maintenance->reason}", $room);
        });

        return redirect()->route('apps.hunian')->with('status', "Perbaikan kamar {$room->room_number} selesai.");
    }

    private function validatedOccupancy(Request $request, int $defaultRent, ?string $lockedStatus = null): array
    {
        $mode = $lockedStatus === 'active' ? 'active' : $request->input('mode');

        $data = $request->validate([
            'mode' => [$lockedStatus === 'active' ? 'nullable' : 'required', 'in:reserved,active'],
            'occupant_name' => ['required', 'string', 'max:120'],
            'occupant_phone' => ['nullable', 'string', 'max:30'],
            'check_in_date' => [$mode === 'active' ? 'required' : 'nullable', 'date'],
            'paid_until' => array_filter(['nullable', 'date', $request->filled('check_in_date') ? 'after_or_equal:check_in_date' : null]),
            'rent_price' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'notes' => ['nullable', 'string', 'max:500'],
            '_dialog' => ['nullable', 'string'],
            '_target' => ['nullable', 'integer'],
        ], [], [
            'occupant_name' => 'nama penghuni',
            'occupant_phone' => 'telepon',
            'check_in_date' => 'tanggal masuk',
            'paid_until' => 'sewa terbayar sampai',
            'rent_price' => 'harga sewa',
        ]);

        return [
            'status' => $mode,
            'occupant_name' => $data['occupant_name'],
            'occupant_phone' => $data['occupant_phone'] ?? null,
            'check_in_date' => $data['check_in_date'] ?? null,
            'paid_until' => $mode === 'active' ? ($data['paid_until'] ?? null) : null,
            'rent_price' => $data['rent_price'] ?? $defaultRent,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function logOccupancy(HunianOccupancy $occupancy, HunianRoom $room, string $action): void
    {
        $description = match ($action) {
            'check_in' => 'Check-in penghuni.',
            'reserved' => 'Kamar dipesan' . ($occupancy->check_in_date ? ' untuk masuk ' . $occupancy->check_in_date->format('d M Y') : '') . '.',
            default => 'Data penghuni diperbarui.',
        };
        HunianLog::record($action, $description, $room, $occupancy->occupant_name);
    }
}
