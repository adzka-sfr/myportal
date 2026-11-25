<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Apps\Concerns\HandlesTicketMessages;
use App\Http\Controllers\Controller;
use App\Models\HunianTicket;
use App\Models\HunianTicketMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Sisi pengelola untuk keluhan penghuni. */
class HunianTicketController extends Controller
{
    use HandlesTicketMessages;

    public function index(Request $request): View
    {
        $filters = $request->validate(['status' => ['nullable', 'in:unread,open,in_progress,resolved']]);
        $status = $filters['status'] ?? null;

        $tickets = HunianTicket::query()
            ->with('tenant:id,full_name')
            ->when($status === 'unread', fn($q) => $q->where('manager_unread', true))
            ->when($status && $status !== 'unread', fn($q) => $q->where('status', $status))
            ->orderByDesc('manager_unread')
            ->orderByRaw("FIELD(status, 'open', 'in_progress', 'resolved')")
            ->orderByDesc('last_message_at')
            ->paginate(20)
            ->withQueryString();

        return view('portal.kelola_hunian.tickets', [
            'tickets' => $tickets,
            'status' => $status,
            'statuses' => HunianTicket::STATUSES,
            'counts' => HunianTicket::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'unread' => HunianTicket::where('manager_unread', true)->count(),
        ]);
    }

    public function show(HunianTicket $ticket): View
    {
        if ($ticket->manager_unread) {
            $ticket->update(['manager_unread' => false]);
        }

        return view('portal.kelola_hunian.ticket', [
            'ticket' => $ticket->load(['tenant', 'messages']),
            'statuses' => HunianTicket::STATUSES,
        ]);
    }

    public function reply(Request $request, HunianTicket $ticket): RedirectResponse
    {
        $request->validate($this->messageRules(), [], $this->messageAttributes());

        $this->storeMessage($ticket, $request, 'manager', fn(HunianTicket $t) => [
            'tenant_unread' => true,
            'manager_unread' => false,
            'status' => $t->status === 'open' ? 'in_progress' : $t->status,
        ]);

        return redirect()->route('apps.hunian.tickets.show', $ticket)->with('status', 'Balasan terkirim.');
    }

    public function updateStatus(Request $request, HunianTicket $ticket): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:' . implode(',', array_keys(HunianTicket::STATUSES))]]);
        $ticket->update(['status' => $data['status'], 'manager_unread' => false, 'tenant_unread' => true]);

        return back()->with('status', 'Status keluhan diperbarui.');
    }

    public function image(HunianTicketMessage $message): Response
    {
        return $this->imageResponse($message);
    }
}
