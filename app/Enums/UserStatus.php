<?php

namespace App\Enums;

/**
 * Tenant account verification status (verified manually by an admin).
 */
enum UserStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Verifikasi',
            self::APPROVED => 'Diterima',
            self::REJECTED => 'Ditolak',
        };
    }
}
