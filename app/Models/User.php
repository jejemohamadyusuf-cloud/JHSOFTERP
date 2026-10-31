<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Support\UserAccessCatalog;
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
        'role',
        'permissions',
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
            'permissions' => 'array',
        ];
    }

    public function hasRole(string ...$roles): bool
    {
        return $this->role === UserRole::Admin->value || in_array($this->role, $roles, true);
    }

    public function canAccess(string $permission): bool
    {
        if ($this->role === UserRole::Admin->value) {
            return true;
        }

        $permissions = $this->permissions ?? UserAccessCatalog::defaultsForRole($this->role);

        return in_array($permission, $permissions, true);
    }

    public function roleLabel(): string
    {
        return UserRole::tryFrom($this->role)?->label() ?? 'Tidak diketahui';
    }
}
