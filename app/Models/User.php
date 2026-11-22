<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Application user: admin or tenant.
 *
 * A new tenant starts as PENDING and can only log in and rent after
 * an admin approves the registration (manual ID-card check).
 *
 * Relationships: one tenant has many Vehicles; one admin processes many RentalExtensions.
 *
 * @author  Christopher
 *
 * @version 1.0
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected $fillable = ['nik', 'name', 'phone', 'email', 'password', 'role', 'status', 'verified_at'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    /** Vehicles owned by this tenant. */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /** Rental extensions processed by this admin. */
    public function approvedExtensions(): HasMany
    {
        return $this->hasMany(RentalExtension::class, 'admin_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isApproved(): bool
    {
        return $this->status === UserStatus::APPROVED;
    }
}
