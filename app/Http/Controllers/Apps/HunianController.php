<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\HunianLog;
use App\Models\HunianMaintenance;
use App\Models\HunianOccupancy;
use App\Models\HunianPayment;
use App\Models\HunianRoom;
use App\Models\HunianTenant;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Aktivitas harian: monitoring kamar, pergantian penghuni, pembayaran, perbaikan. */
class HunianController extends Controller
{
    public const PROOF_DIR = 'hunian/proofs';

    public function index(): View
    {
        $rooms = HunianRoom::query()
            ->with(['facilities', 'activeOccupancy', 'reservation', 'openMaintenance'])
            ->orderBy('floor')->orderByRaw('LENGTH(room_number)')->orderBy('room_number')
            ->get();

        $byStatus = $rooms->groupBy(fn(HunianRoom $room) => $room->currentStatus());
        $occupied = $byStatus->get('occupied', collect());
        $pending = HunianPayment::query()->where('status', 'pending');
        $stats = [
            'total' => $rooms->count(),
            'available' => $byStatus->get('available', collect())->count(),
            'occupied' => $occupied->count(),
            'reserved' => $byStatus->get('reserved', collect())->count(),
            'maintenance' => $byStatus->get('maintenance', collect())->count(),
            'overdue' => $occupied->filter(fn(HunianRoom $room) => $room->activeOccupancy->paymentState() === 'overdue')->count(),
            // Pemasukan hanya dihitung dari pembayaran yang sudah di-match.
            'income' => (int) HunianPayment::query()->where('status', 'matched')
                ->whereBetween('matched_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'pending_count' => (clone $pending)->count(),
            'pending_amount' => (int) (clone $pending)->sum('amount'),
        ];
        $stats['occupancy'] = $stats['total'] > 0 ? (int) round($stats['occupied'] / $stats['total'] * 100) : 0;

        return view('portal.kelola_hunian.index', [
            'floors' => $rooms->groupBy('floor'),
            'stats' => $stats,
            'statuses' => HunianRoom::STATUSES,
            // Hanya penghuni aktif yang belum menempati / memesan kamar mana pun.
            'tenantOptions' => HunianTenant::query()->where('is_active', true)
                ->whereDoesntHave('occupancies', fn($q) => $q->whereIn('status', ['active', 'reserved']))
                ->orderBy('full_name')->get(['id', 'full_name', 'phone']),
        ]);
    }

    public function occupy(Request $request, HunianRoom $room): RedirectResponse
    {
        if ($room->activeOccupancy()->exists() || $room->reservation()->exists() || $room->openMaintenance()->exists()) {
            return back()->withErrors(['room' => "Kamar {$room->room_number} sedang tidak kosong."]);
        }

        ['occupancy' => $data, 'period' => $period] = $this->validatedOccupancy($request, $room->monthly_price);
        $resolved = $period ? $this->resolvePeriod($period, $data['check_in_date'], $data['rent_price']) : null;
        if ($resolved) {
            $data['paid_until'] = $resolved['end'];
        }

        $occupancy = $this->withProof($request, $resolved !== null, function (?string $proof) use ($room, $data, $resolved) {
            $occupancy = $room->occupancies()->create($data);
            $this->logOccupancy($occupancy, $room, $data['status'] === 'active' ? 'check_in' : 'reserved');
            if ($resolved) {
                $this->createPayment($occupancy, $room, $resolved, $proof);
            }

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

        ['occupancy' => $data, 'period' => $period] = $this->validatedOccupancy($request, $occupancy->rent_price, $occupancy);
        $resolved = $period ? $this->resolvePeriod($period, $data['check_in_date'], $data['rent_price']) : null;
        if ($resolved) {
            $data['paid_until'] = $resolved['end'];
        }

        $this->withProof($request, $resolved !== null, function (?string $proof) use ($occupancy, $room, $data, $resolved, $wasReserved) {
            $occupancy->update($data);
            $this->logOccupancy($occupancy, $room, $wasReserved && $data['status'] === 'active' ? 'check_in' : 'occupant_updated');
            if ($resolved) {
                $this->createPayment($occupancy, $room, $resolved, $proof);
            }
        });

        return redirect()->route('apps.hunian')->with('status', "Data kamar {$room->room_number} diperbarui.");
    }

    public function pay(Request $request, HunianOccupancy $occupancy): RedirectResponse
    {
        abort_unless($occupancy->status === 'active', 404);
        $data = $request->validate(
            ['period_start' => ['required', 'date'], '_dialog' => ['nullable', 'string'], '_target' => ['nullable', 'integer']]
                + $this->periodRules($request, 'period_start'),
            [],
            $this->periodAttributes() + ['period_start' => 'mulai dari tanggal'],
        );

        $room = $occupancy->room;
        $resolved = $this->resolvePeriod($data, $data['period_start'], $occupancy->rent_price);

        $this->withProof($request, true, function (?string $proof) use ($occupancy, $room, $resolved) {
            $end = Carbon::parse($resolved['end']);
            if (! $occupancy->paid_until || $end->gt($occupancy->paid_until)) {
                $occupancy->update(['paid_until' => $end]);
            }
            $this->createPayment($occupancy, $room, $resolved, $proof);
        });

        return redirect()->route('apps.hunian')->with('status', "Pembayaran kamar {$room->room_number} dicatat, menunggu verifikasi.");
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

    /** @return array{occupancy: array, period: ?array} */
    private function validatedOccupancy(Request $request, int $defaultRent, ?HunianOccupancy $current = null): array
    {
        $lockedStatus = $current?->status;
        $mode = $lockedStatus === 'active' ? 'active' : $request->input('mode');
        $needsPeriod = $mode === 'active' && $lockedStatus !== 'active';
        // Penghuni yang sedang menghuni tidak boleh diganti di sini; pergantian = check-out lalu check-in.
        $needsTenant = $lockedStatus !== 'active';

        $data = $request->validate([
            'mode' => [$lockedStatus === 'active' ? 'nullable' : 'required', 'in:reserved,active'],
            'tenant_id' => $needsTenant ? [
                'required',
                'integer',
                Rule::exists('hunian_tenants', 'id')->where('is_active', 1),
                function (string $attribute, mixed $value, Closure $fail) use ($current) {
                    $busy = HunianOccupancy::query()->where('tenant_id', $value)->whereIn('status', ['active', 'reserved'])
                        ->when($current, fn($q) => $q->whereKeyNot($current->id))->exists();
                    if ($busy) {
                        $fail('Penghuni ini masih menghuni atau memesan kamar lain.');
                    }
                },
            ] : ['nullable'],
            'check_in_date' => [$mode === 'active' ? 'required' : 'nullable', 'date'],
            'paid_until' => ['nullable', 'date'],
            'rent_price' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'notes' => ['nullable', 'string', 'max:500'],
            '_dialog' => ['nullable', 'string'],
            '_target' => ['nullable', 'integer'],
        ] + ($needsPeriod ? $this->periodRules($request, 'check_in_date') : []), [
            'tenant_id.exists' => 'Penghuni tidak ditemukan atau sudah tidak aktif.',
        ], [
            'tenant_id' => 'penghuni',
            'check_in_date' => 'tanggal masuk',
            'paid_until' => 'sewa terbayar sampai',
            'rent_price' => 'harga sewa',
        ] + $this->periodAttributes());

        $tenant = $needsTenant ? HunianTenant::findOrFail($data['tenant_id']) : null;

        return [
            'occupancy' => [
                'status' => $mode,
                'check_in_date' => $data['check_in_date'] ?? null,
                // Check-in baru: diisi dari hasil perhitungan periode.
                'paid_until' => $lockedStatus === 'active' ? ($data['paid_until'] ?? null) : null,
                'rent_price' => $data['rent_price'] ?? $defaultRent,
                'notes' => $data['notes'] ?? null,
            ] + ($tenant ? [
                'tenant_id' => $tenant->id,
                'occupant_name' => $tenant->full_name,
                'occupant_phone' => $tenant->phone,
            ] : []),
            'period' => $needsPeriod ? Arr::only($data, ['period_type', 'period_qty', 'period_end', 'amount']) : null,
        ];
    }

    private function periodRules(Request $request, string $startField): array
    {
        $custom = $request->input('period_type') === 'custom';

        return [
            'period_type' => ['required', Rule::in(array_keys(HunianPayment::PERIODS))],
            'period_qty' => [$custom ? 'nullable' : 'required', 'integer', 'min:1', 'max:365'],
            'period_end' => $custom
                ? array_filter(['required', 'date', $request->filled($startField) ? "after_or_equal:{$startField}" : null])
                : ['nullable', 'date'],
            'amount' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    private function periodAttributes(): array
    {
        return [
            'period_type' => 'periode',
            'period_qty' => 'jumlah periode',
            'period_end' => 'tanggal keluar',
            'amount' => 'nominal',
            'proof' => 'bukti pembayaran',
        ];
    }

    /** Hitung rentang dan nominal; melempar error validasi bila nominal tidak bisa ditentukan. */
    private function resolvePeriod(array $data, string $start, int $monthlyRent): array
    {
        $type = $data['period_type'];
        $qty = $type === 'custom' ? null : (int) $data['period_qty'];
        $startDate = Carbon::parse($start)->startOfDay();
        $amount = isset($data['amount']) ? (int) $data['amount'] : HunianPayment::defaultAmount($type, $qty, $monthlyRent);

        if ($amount === null) {
            throw ValidationException::withMessages(['amount' => 'Nominal wajib diisi untuk periode ini.']);
        }

        return [
            'type' => $type,
            'qty' => $qty,
            'start' => $startDate,
            'end' => HunianPayment::periodEnd($type, $qty, $startDate, $data['period_end'] ?? null),
            'amount' => $amount,
        ];
    }

    /** Simpan gambar bukti (bila ada) lalu jalankan transaksi; gambar dibersihkan jika transaksi gagal. */
    private function withProof(Request $request, bool $accepts, Closure $callback): mixed
    {
        $path = $accepts ? $request->file('proof')?->store(self::PROOF_DIR, 'local') : null;

        try {
            return DB::transaction(fn() => $callback($path));
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
    }

    private function createPayment(HunianOccupancy $occupancy, HunianRoom $room, array $resolved, ?string $proofPath): void
    {
        $payment = HunianPayment::create([
            'occupancy_id' => $occupancy->id,
            'room_id' => $room->id,
            'room_number' => $room->room_number,
            'occupant_name' => $occupancy->occupant_name,
            'period_type' => $resolved['type'],
            'period_qty' => $resolved['qty'],
            'period_start' => $resolved['start'],
            'period_end' => $resolved['end'],
            'amount' => $resolved['amount'],
            'proof_path' => $proofPath,
            'status' => 'pending',
        ]);

        HunianLog::record(
            'payment',
            "Pembayaran {$payment->periodLabel()} ({$payment->period_start->format('d M Y')} s.d. {$payment->period_end->format('d M Y')}), menunggu verifikasi.",
            $room,
            $occupancy->occupant_name,
            $payment->amount,
        );
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
