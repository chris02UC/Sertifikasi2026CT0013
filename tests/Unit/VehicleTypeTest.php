<?php

namespace Tests\Unit;

use App\Enums\GarageType;
use App\Enums\VehicleType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for which garage types each vehicle may use.
 */
class VehicleTypeTest extends TestCase
{
    #[Test] // UT-38 to UT-45
    #[DataProvider('placements')]
    public function can_use_follows_the_placement_rules(VehicleType $vehicle, GarageType $garage, bool $expected): void
    {
        $this->assertSame($expected, $vehicle->canUse($garage));
    }

    public static function placements(): array
    {
        return [
            'bicycle -> bicycle garage' => [VehicleType::BICYCLE, GarageType::BICYCLE, true],
            'motorcycle -> bicycle garage' => [VehicleType::MOTORCYCLE, GarageType::BICYCLE, true],
            'motorcycle -> car garage (rejected)' => [VehicleType::MOTORCYCLE, GarageType::CAR, false],
            'car -> car garage' => [VehicleType::CAR, GarageType::CAR, true],
            'car -> electric garage (rejected)' => [VehicleType::CAR, GarageType::ELECTRIC, false],
            'electric car -> car garage' => [VehicleType::ELECTRIC_CAR, GarageType::CAR, true],
            'electric car -> electric garage' => [VehicleType::ELECTRIC_CAR, GarageType::ELECTRIC, true],
            'electric car -> bicycle garage (rejected)' => [VehicleType::ELECTRIC_CAR, GarageType::BICYCLE, false],
        ];
    }

    #[Test] // UT-46
    public function requires_plate_for_every_vehicle_except_bicycle(): void
    {
        $this->assertFalse(VehicleType::BICYCLE->requiresPlate());
        $this->assertTrue(VehicleType::MOTORCYCLE->requiresPlate());
        $this->assertTrue(VehicleType::ELECTRIC_CAR->requiresPlate());
    }

    #[Test] // UT-47
    public function requires_battery_only_for_electric_car(): void
    {
        $this->assertTrue(VehicleType::ELECTRIC_CAR->requiresBattery());
        $this->assertFalse(VehicleType::CAR->requiresBattery());
    }
}
