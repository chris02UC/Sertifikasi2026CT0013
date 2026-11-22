<?php

namespace App\Enums;

/**
 * Garage status. 'used' while the garage has a current or upcoming rental.
 */
enum GarageStatus: string
{
    case AVAILABLE = 'available';
    case USED = 'used';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Tersedia',
            self::USED => 'Terpakai',
        };
    }
}
