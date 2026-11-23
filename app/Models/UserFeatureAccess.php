<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFeatureAccess extends Model
{
    protected $table = 'user_feature_access';

    protected $fillable = ['application_slug', 'feature_slug'];
}
