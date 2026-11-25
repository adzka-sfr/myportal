<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HunianTicket extends Model
{
    public const STATUSES = ['open' => 'Baru', 'in_progress' => 'Diproses', 'resolved' => 'Selesai'];

    public const CATEGORIES = ['Fasilitas rusak', 'Listrik / air', 'Kebersihan', 'Keamanan', 'Pembayaran', 'Lainnya'];

    protected $fillable = [
        'tenant_id',
        'room_id',
        'room_number',
        'subject',
        'category',
        'status',
        'manager_unread',
        'tenant_unread',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return ['manager_unread' => 'boolean', 'tenant_unread' => 'boolean', 'last_message_at' => 'datetime'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(HunianTenant::class, 'tenant_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(HunianTicketMessage::class, 'ticket_id')->orderBy('id');
    }
}
