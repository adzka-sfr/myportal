<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HunianFacility extends Model
{
    protected $fillable = ['name'];

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(HunianRoom::class, 'hunian_room_facility', 'facility_id', 'room_id');
    }
}
