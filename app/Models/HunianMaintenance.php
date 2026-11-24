<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HunianMaintenance extends Model
{
    protected $fillable = ['room_id', 'reason', 'started_at', 'finished_at'];

    protected function casts(): array
    {
        return ['started_at' => 'date', 'finished_at' => 'date'];
    }
}
