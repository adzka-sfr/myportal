<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HunianRoom extends Model
{
    public const STATUSES = [
        'available' => 'Kosong',
        'occupied' => 'Terisi',
        'reserved' => 'Dipesan',
        'maintenance' => 'Perbaikan',
    ];

    protected $fillable = ['room_number', 'floor', 'room_type', 'monthly_price', 'notes'];

    protected function casts(): array
    {
        return ['floor' => 'integer', 'monthly_price' => 'integer'];
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(HunianFacility::class, 'hunian_room_facility', 'room_id', 'facility_id')->orderBy('name');
    }

    public function occupancies(): HasMany
    {
        return $this->hasMany(HunianOccupancy::class, 'room_id');
    }

    public function activeOccupancy(): HasOne
    {
        return $this->hasOne(HunianOccupancy::class, 'room_id')->where('status', 'active');
    }

    public function reservation(): HasOne
    {
        return $this->hasOne(HunianOccupancy::class, 'room_id')->where('status', 'reserved');
    }

    public function openMaintenance(): HasOne
    {
        return $this->hasOne(HunianMaintenance::class, 'room_id')->whereNull('finished_at');
    }

    /** Status aktual hasil turunan dari data operasional (bukan kolom tersimpan). */
    public function currentStatus(): string
    {
        return match (true) {
            $this->openMaintenance !== null => 'maintenance',
            $this->activeOccupancy !== null => 'occupied',
            $this->reservation !== null => 'reserved',
            default => 'available',
        };
    }
}
