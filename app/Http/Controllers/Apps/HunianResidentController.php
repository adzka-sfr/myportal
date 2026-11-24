<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\HunianOccupancy;
use App\Models\HunianRoom;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HunianResidentController extends Controller
{
    public const STATUSES = ['active' => 'Menghuni', 'reserved' => 'Booking', 'ended' => 'Sudah keluar'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'room' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:' . implode(',', array_keys(self::STATUSES))],
        ]);

        $residents = HunianOccupancy::query()
            ->with('room:id,room_number,floor')
            ->when($filters['q'] ?? null, fn($q, $term) => $q->where('occupant_name', 'like', '%' . addcslashes($term, '%_\\') . '%'))
            ->when($filters['room'] ?? null, fn($q, $room) => $q->where('room_id', $room))
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->orderByRaw("FIELD(status, 'active', 'reserved', 'ended')")
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('portal.kelola_hunian.residents', [
            'residents' => $residents,
            'statuses' => self::STATUSES,
            'counts' => HunianOccupancy::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'rooms' => HunianRoom::query()->orderByRaw('LENGTH(room_number)')->orderBy('room_number')->get(['id', 'room_number']),
            'filters' => $filters,
        ]);
    }
}
