<?php

namespace App\Enums;

/**
 * Garage type and its garage-code prefix (S, C, E).
 */
enum GarageType: string
{
    case BICYCLE = 'bicycle';
    case CAR = 'car';
    case ELECTRIC = 'electric';

    /** First letter of the garage code, e.g. S001, C010, E005. */
    public function codePrefix(): string
    {
        return match ($this) {
            self::BICYCLE => 'S',
            self::CAR => 'C',
            self::ELECTRIC => 'E',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::BICYCLE => 'Garasi Sepeda',
            self::CAR => 'Garasi Mobil',
            self::ELECTRIC => 'Garasi Mobil Listrik',
        };
    }
}
