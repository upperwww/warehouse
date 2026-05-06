<?php

namespace App\Models;

use App\Bundles\Warehouse\Utils\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'avatar_path',
        'is_active',
        'phone',
        'position',
        'department',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin->value);
    }

    public function canManageWarehouse(): bool
    {
        return $this->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value]);
    }

    public function canViewWarehouseHistory(): bool
    {
        return $this->canManageWarehouse();
    }
}
