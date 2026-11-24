<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\HunianLog;
use App\Models\HunianRoom;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HunianLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'room' => ['nullable', 'string', 'max:20'],
            'action' => ['nullable', 'in:' . implode(',', array_keys(HunianLog::ACTIONS))],
        ]);

        $logs = HunianLog::query()
            ->when($filters['room'] ?? null, fn($q, $room) => $q->where('room_number', $room))
            ->when($filters['action'] ?? null, fn($q, $action) => $q->where('action', $action))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('portal.kelola_hunian.logs', [
            'logs' => $logs,
            'actions' => HunianLog::ACTIONS,
            'roomNumbers' => HunianRoom::query()->orderByRaw('LENGTH(room_number)')->orderBy('room_number')->pluck('room_number'),
            'filters' => $filters,
        ]);
    }
}
