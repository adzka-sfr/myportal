<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HunianTicketMessage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['ticket_id', 'sender', 'sender_name', 'body', 'image_path'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(HunianTicket::class, 'ticket_id');
    }
}
