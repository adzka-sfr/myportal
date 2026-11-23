<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserApplicationAccess extends Model
{
    protected $table = 'user_application_access';

    protected $fillable = ['application_slug'];
}
