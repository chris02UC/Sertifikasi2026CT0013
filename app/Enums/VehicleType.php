<?php

namespace App\Enums;

/**
 * Vehicle type and the garage types it may use.
 */
enum VehicleType: string
{
    case BICYCLE = 'bicycle';
    case MOTORCYCLE = 'motorcycle';
    case CAR = 'car';
    case ELECTRIC_CAR = 'electric_car';

    /**
     * Garage types this vehicle may be parked in.
     * Bicycle/motorcycle -> bicycle garage; car -> car garage;
     * electric car -> car garage or electric-car garage.
     *
     * @return GarageType[]
     */
    public function allowedGarageTypes(): array
    {
        return match ($this) {
            self::BICYCLE, self::MOTORCYCLE => [GarageType::BICYCLE],
            self::CAR => [GarageType::CAR],
            self::ELECTRIC_CAR => [GarageType::CAR, GarageType::ELECTRIC],
        };
    }

    public function canUse(GarageType $garageType): bool
    {
        return in_array($garageType, $this->allowedGarageTypes(), true);
    }

    /** A plate number is required for every vehicle except bicycles. */
    public function requiresPlate(): bool
    {
        return $this !== self::BICYCLE;
    }

    /** Battery capacity (kWh) applies to electric cars only. */
    public function requiresBattery(): bool
    {
        return $this === self::ELECTRIC_CAR;
    }

    public function label(): string
    {
        return match ($this) {
            self::BICYCLE => 'Sepeda',
            self::MOTORCYCLE => 'Sepeda Motor',
            self::CAR => 'Mobil',
            self::ELECTRIC_CAR => 'Mobil Listrik',
        };
    }
}
