<?php

namespace App\Enums;

/**
 * Rental type: daily or monthly.
 */
enum RentalType: string
{
    case DAILY = 'daily';
    case MONTHLY = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'Harian',
            self::MONTHLY => 'Bulanan',
        };
    }
}
