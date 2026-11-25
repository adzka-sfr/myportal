<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HunianOccupancy extends Model
{
    protected $fillable = [
        'room_id',
        'tenant_id',
        'status',
        'occupant_name',
        'occupant_phone',
        'check_in_date',
        'paid_until',
        'rent_price',
        'notes',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'paid_until' => 'date',
            'ended_at' => 'date',
            'rent_price' => 'integer',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(HunianRoom::class, 'room_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(HunianTenant::class, 'tenant_id');
    }

    /** Selisih hari ke jatuh tempo (negatif = terlambat), null bila belum ada tanggal. */
    public function daysUntilDue(): ?int
    {
        return $this->paid_until ? (int) now()->startOfDay()->diffInDays($this->paid_until->startOfDay(), false) : null;
    }

    /** ok, soon (<= 7 hari), overdue; null bila bukan penghuni aktif atau tanpa tanggal. */
    public function paymentState(): ?string
    {
        $days = $this->status === 'active' ? $this->daysUntilDue() : null;

        return $days === null ? null : ($days < 0 ? 'overdue' : ($days <= 7 ? 'soon' : 'ok'));
    }
}
