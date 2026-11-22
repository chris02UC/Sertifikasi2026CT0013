<?php

namespace App\Enums;

/**
 * Application user role.
 */
enum UserRole: string
{
    case ADMIN = 'admin';
    case TENANT = 'tenant';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::TENANT => 'Penyewa',
        };
    }
}
