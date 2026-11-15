<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_superadmin',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_superadmin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessApplication(string $application): bool
    {
        return $this->is_superadmin || $this->applicationAccess()->where('application_slug', $application)->exists();
    }

    public function canUseFeature(string $application, string $feature): bool
    {
        return $this->is_superadmin || (
            $this->canAccessApplication($application)
            && $this->featureAccess()->where('application_slug', $application)->where('feature_slug', $feature)->exists()
        );
    }

    public function applicationAccess()
    {
        return $this->hasMany(UserApplicationAccess::class);
    }

    public function featureAccess()
    {
        return $this->hasMany(UserFeatureAccess::class);
    }

    public function shoppingLists()
    {
        return $this->hasMany(ShoppingList::class);
    }
}
