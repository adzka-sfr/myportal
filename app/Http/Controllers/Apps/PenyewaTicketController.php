<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Apps\Concerns\HandlesTicketMessages;
use App\Http\Controllers\Controller;
use App\Models\HunianTenant;
use App\Models\HunianTicket;
use App\Models\HunianTicketMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Keluhan sisi penghuni; setiap tiket dan pesan diverifikasi milik penghuni yang login. */
class PenyewaTicketController extends Controller
{
    use HandlesTicketMessages;

    public function index(Request $request): View
    {
        $tenant = $this->tenant($request);

        return view('portal.penyewa_hunian.tickets', [
            'tenant' => $tenant,
            'tickets' => $tenant->tickets()->orderByDesc('tenant_unread')->orderByRaw("FIELD(status, 'open', 'in_progress', 'resolved')")->latest('last_message_at')->paginate(15),
            'statuses' => HunianTicket::STATUSES,
            'categories' => HunianTicket::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant($request);
        $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(HunianTicket::CATEGORIES)],
            'body' => ['required', 'string', 'max:2000'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [], ['subject' => 'judul', 'category' => 'kategori', 'body' => 'deskripsi', 'image' => 'foto']);

        $room = $tenant->activeOccupancy()->with('room:id,room_number')->first()?->room;

        $ticket = DB::transaction(function () use ($request, $tenant, $room) {
            $ticket = HunianTicket::create([
                'tenant_id' => $tenant->id,
                'room_id' => $room?->id,
                'room_number' => $room?->room_number,
                'subject' => $request->input('subject'),
                'category' => $request->input('category'),
                'status' => 'open',
                'manager_unread' => true,
                'last_message_at' => now(),
            ]);
            $this->storeMessage($ticket, $request, 'tenant', fn() => ['manager_unread' => true]);

            return $ticket;
        });

        return redirect()->route('apps.penyewa.tickets.show', $ticket)->with('status', 'Keluhan terkirim ke pengelola.');
    }

    public function show(Request $request, HunianTicket $ticket): View
    {
        $this->authorizeTicket($request, $ticket);
        if ($ticket->tenant_unread) {
            $ticket->update(['tenant_unread' => false]);
        }

        return view('portal.penyewa_hunian.ticket', [
            'tenant' => $this->tenant($request),
            'ticket' => $ticket->load('messages'),
            'statuses' => HunianTicket::STATUSES,
        ]);
    }

    public function reply(Request $request, HunianTicket $ticket): RedirectResponse
    {
        $this->authorizeTicket($request, $ticket);
        $request->validate($this->messageRules(), [], $this->messageAttributes());

        $this->storeMessage($ticket, $request, 'tenant', fn(HunianTicket $t) => [
            'manager_unread' => true,
            'tenant_unread' => false,
            // Membalas tiket yang sudah selesai membukanya kembali.
            'status' => $t->status === 'resolved' ? 'open' : $t->status,
        ]);

        return redirect()->route('apps.penyewa.tickets.show', $ticket)->with('status', 'Pesan terkirim.');
    }

    public function image(Request $request, HunianTicketMessage $message): Response
    {
        $this->authorizeTicket($request, $message->ticket);

        return $this->imageResponse($message);
    }

    private function tenant(Request $request): HunianTenant
    {
        return $request->attributes->get('tenant');
    }

    private function authorizeTicket(Request $request, HunianTicket $ticket): void
    {
        abort_unless($ticket->tenant_id === $this->tenant($request)->id, 404);
    }
}
