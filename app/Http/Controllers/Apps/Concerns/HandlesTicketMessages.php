<?php

namespace App\Http\Controllers\Apps\Concerns;

use App\Models\HunianTicket;
use App\Models\HunianTicketMessage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/** Logika pesan keluhan yang dipakai bersama oleh sisi pengelola dan sisi penghuni. */
trait HandlesTicketMessages
{
    protected function messageRules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:2000', 'required_without:image'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    protected function messageAttributes(): array
    {
        return ['body' => 'pesan', 'image' => 'foto'];
    }

    /** Simpan pesan (dan foto bila ada) bersama perubahan tiket dalam satu transaksi. */
    protected function storeMessage(HunianTicket $ticket, Request $request, string $sender, Closure $ticketChanges): HunianTicketMessage
    {
        $path = $request->file('image')?->store('hunian/tickets', 'local');

        try {
            return DB::transaction(function () use ($ticket, $request, $sender, $ticketChanges, $path) {
                $message = $ticket->messages()->create([
                    'sender' => $sender,
                    'sender_name' => $request->user()->name,
                    'body' => $request->input('body') ?: null,
                    'image_path' => $path,
                ]);
                $ticket->update(['last_message_at' => now()] + $ticketChanges($ticket));

                return $message;
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
    }

    protected function imageResponse(HunianTicketMessage $message): Response
    {
        abort_unless($message->image_path && Storage::disk('local')->exists($message->image_path), 404);

        return Storage::disk('local')->response($message->image_path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
