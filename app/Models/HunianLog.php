<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HunianLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTIONS = [
        'room_created' => 'Kamar dibuat',
        'room_updated' => 'Kamar diubah',
        'room_deleted' => 'Kamar dihapus',
        'tenant_created' => 'Penghuni ditambahkan',
        'tenant_updated' => 'Data penghuni diubah',
        'tenant_deleted' => 'Penghuni dihapus',
        'facility_created' => 'Fasilitas dibuat',
        'facility_updated' => 'Fasilitas diubah',
        'facility_deleted' => 'Fasilitas dihapus',
        'reserved' => 'Booking',
        'reservation_cancelled' => 'Booking dibatalkan',
        'check_in' => 'Check-in',
        'occupant_updated' => 'Data penghuni diubah',
        'payment' => 'Pembayaran',
        'payment_matched' => 'Pembayaran cocok',
        'proof_deleted' => 'Bukti dihapus',
        'check_out' => 'Check-out',
        'maintenance_started' => 'Perbaikan dimulai',
        'maintenance_finished' => 'Perbaikan selesai',
    ];

    protected $fillable = ['room_id', 'room_number', 'action', 'description', 'occupant_name', 'amount', 'actor_id', 'actor_name'];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    public static function record(string $action, string $description, ?HunianRoom $room = null, ?string $occupant = null, ?int $amount = null): self
    {
        $user = auth()->user();

        return static::create([
            'room_id' => $room?->id,
            'room_number' => $room?->room_number,
            'action' => $action,
            'description' => $description,
            'occupant_name' => $occupant,
            'amount' => $amount,
            'actor_id' => $user?->id,
            'actor_name' => $user?->name,
        ]);
    }
}
