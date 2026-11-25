<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HunianTenant extends Model
{
    protected $fillable = ['user_id', 'full_name', 'nik', 'phone', 'ktp_address', 'ktp_photo_path', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function occupancies(): HasMany
    {
        return $this->hasMany(HunianOccupancy::class, 'tenant_id');
    }

    public function activeOccupancy(): HasOne
    {
        return $this->hasOne(HunianOccupancy::class, 'tenant_id')->where('status', 'active');
    }

    public function reservation(): HasOne
    {
        return $this->hasOne(HunianOccupancy::class, 'tenant_id')->where('status', 'reserved');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(HunianTicket::class, 'tenant_id');
    }

    public function maskedNik(): string
    {
        return substr($this->nik, 0, 4) . str_repeat('•', 8) . substr($this->nik, -4);
    }
}
