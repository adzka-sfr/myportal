<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\HunianFacility;
use App\Models\HunianLog;
use App\Models\HunianRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Data master: kamar dan fasilitas. Jarang berubah, dipisah dari monitoring. */
class HunianMasterController extends Controller
{
    public function index(): View
    {
        return view('portal.kelola_hunian.master', [
            'rooms' => HunianRoom::query()->with('facilities')
                ->orderBy('floor')->orderByRaw('LENGTH(room_number)')->orderBy('room_number')->get(),
            'facilities' => HunianFacility::query()->withCount('rooms')->orderBy('name')->get(),
        ]);
    }

    public function storeRoom(Request $request): RedirectResponse
    {
        $data = $this->validatedRoom($request);
        $room = DB::transaction(function () use ($data) {
            $room = HunianRoom::create($data['room']);
            $room->facilities()->sync($data['facilities']);
            HunianLog::record('room_created', "Kamar {$room->room_number} (lantai {$room->floor}) dibuat.", $room);

            return $room;
        });

        return redirect()->route('apps.hunian.master')->with('status', "Kamar {$room->room_number} ditambahkan.");
    }

    public function updateRoom(Request $request, HunianRoom $room): RedirectResponse
    {
        $data = $this->validatedRoom($request, $room);
        DB::transaction(function () use ($room, $data) {
            $room->update($data['room']);
            $room->facilities()->sync($data['facilities']);
            HunianLog::record('room_updated', "Data master kamar {$room->room_number} diubah.", $room);
        });

        return redirect()->route('apps.hunian.master')->with('status', "Kamar {$room->room_number} diperbarui.");
    }

    public function destroyRoom(HunianRoom $room): RedirectResponse
    {
        if ($room->occupancies()->whereIn('status', ['active', 'reserved'])->exists() || $room->openMaintenance()->exists()) {
            return back()->withErrors(['room' => "Kamar {$room->room_number} masih terisi, dipesan, atau dalam perbaikan."]);
        }

        DB::transaction(function () use ($room) {
            HunianLog::record('room_deleted', "Kamar {$room->room_number} dihapus dari master.", $room);
            $room->delete();
        });

        return redirect()->route('apps.hunian.master')->with('status', "Kamar {$room->room_number} dihapus.");
    }

    public function storeFacility(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', 'unique:hunian_facilities,name']], [], ['name' => 'nama fasilitas']);
        $facility = HunianFacility::create($data);
        HunianLog::record('facility_created', "Fasilitas \"{$facility->name}\" dibuat.");

        return redirect()->route('apps.hunian.master')->with('status', 'Fasilitas ditambahkan.');
    }

    public function updateFacility(Request $request, HunianFacility $facility): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', Rule::unique('hunian_facilities', 'name')->ignore($facility->id)]], [], ['name' => 'nama fasilitas']);
        $old = $facility->name;
        $facility->update($data);
        HunianLog::record('facility_updated', "Fasilitas \"{$old}\" diubah menjadi \"{$facility->name}\".");

        return redirect()->route('apps.hunian.master')->with('status', 'Fasilitas diperbarui.');
    }

    public function destroyFacility(HunianFacility $facility): RedirectResponse
    {
        $facility->delete();
        HunianLog::record('facility_deleted', "Fasilitas \"{$facility->name}\" dihapus.");

        return redirect()->route('apps.hunian.master')->with('status', 'Fasilitas dihapus.');
    }

    private function validatedRoom(Request $request, ?HunianRoom $room = null): array
    {
        $data = $request->validate([
            'room_number' => ['required', 'string', 'max:20', Rule::unique('hunian_rooms', 'room_number')->ignore($room?->id)],
            'floor' => ['required', 'integer', 'min:0', 'max:99'],
            'room_type' => ['nullable', 'string', 'max:60'],
            'monthly_price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'notes' => ['nullable', 'string', 'max:500'],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['integer', 'exists:hunian_facilities,id'],
            '_edit_id' => ['nullable', 'integer'],
        ], [], [
            'room_number' => 'nomor kamar',
            'floor' => 'lantai',
            'room_type' => 'tipe kamar',
            'monthly_price' => 'harga sewa',
            'notes' => 'catatan',
        ]);

        return ['facilities' => $data['facilities'] ?? [], 'room' => collect($data)->only(['room_number', 'floor', 'room_type', 'monthly_price', 'notes'])->all()];
    }
}
