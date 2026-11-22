<?php

namespace Tests\Unit;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Models\Garage;
use App\Models\Vehicle;
use App\Services\PricingService;
use Carbon\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit test (white-box) for all cost calculations.
 */
class PricingServiceTest extends TestCase
{
    private PricingService $pricing;

    protected function setUp(): void
    {
        $this->pricing = new PricingService;
    }

    #[Test] // UT-18
    public function rent_cost_daily_five_days_is_price_times_days(): void
    {
        $this->assertSame(50000, $this->pricing->rentCost(RentalType::DAILY, 10000, 5));
    }

    #[Test] // UT-19
    public function rent_cost_monthly_is_25_times_daily(): void
    {
        $this->assertSame(250000, $this->pricing->rentCost(RentalType::MONTHLY, 10000, 31));
        $this->assertSame(250000, $this->pricing->rentCost(RentalType::MONTHLY, 10000, 28));
    }

    #[Test] // UT-20 to UT-22
    #[DataProvider('invalidRentInputs')]
    public function rent_cost_invalid_input_throws_exception(int $dailyPrice, int $days): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pricing->rentCost(RentalType::DAILY, $dailyPrice, $days);
    }

    public static function invalidRentInputs(): array
    {
        return ['zero days' => [10000, 0], 'negative days' => [10000, -1], 'zero price' => [0, 3]];
    }

    #[Test] // UT-23, empty battery: the whole capacity is charged
    public function charging_cost_empty_battery_is_capacity_times_kwh_price(): void
    {
        $vehicle = new Vehicle(['type' => VehicleType::ELECTRIC_CAR, 'battery_kwh' => 60]);
        $garage = new Garage(['type' => GarageType::ELECTRIC, 'daily_price' => 40000, 'kwh_price' => 2500]);

        $this->assertSame(150000, $this->pricing->chargingCost($vehicle, $garage, 0));
    }

    #[Test] // UT-24, battery 60% full: only the missing 24 kWh are charged
    public function charging_cost_partly_charged_battery_pays_only_missing_kwh(): void
    {
        $vehicle = new Vehicle(['type' => VehicleType::ELECTRIC_CAR, 'battery_kwh' => 60]);
        $garage = new Garage(['type' => GarageType::ELECTRIC, 'daily_price' => 40000, 'kwh_price' => 2500]);

        $this->assertSame(60000, $this->pricing->chargingCost($vehicle, $garage, 36));
    }

    #[Test] // UT-25, boundary: battery already full
    public function charging_cost_full_battery_is_zero(): void
    {
        $vehicle = new Vehicle(['type' => VehicleType::ELECTRIC_CAR, 'battery_kwh' => 60]);
        $garage = new Garage(['type' => GarageType::ELECTRIC, 'daily_price' => 40000, 'kwh_price' => 2500]);

        $this->assertSame(0, $this->pricing->chargingCost($vehicle, $garage, 60));
    }

    #[Test] // UT-26
    public function charging_cost_electric_car_in_car_garage_is_zero(): void
    {
        $vehicle = new Vehicle(['type' => VehicleType::ELECTRIC_CAR, 'battery_kwh' => 60]);
        $garage = new Garage(['type' => GarageType::CAR, 'daily_price' => 25000]);

        $this->assertSame(0, $this->pricing->chargingCost($vehicle, $garage, 0));
    }

    #[Test] // UT-27, large capacity is still charged in full
    public function charging_cost_5000_kwh_capacity_is_charged_in_full(): void
    {
        $vehicle = new Vehicle(['type' => VehicleType::ELECTRIC_CAR, 'battery_kwh' => 5000]);
        $garage = new Garage(['type' => GarageType::ELECTRIC, 'daily_price' => 40000, 'kwh_price' => 1000]);

        $this->assertSame(5000000, $this->pricing->chargingCost($vehicle, $garage, 0));
    }

    #[Test] // UT-28, charge left above the capacity is refused
    public function charging_cost_charge_left_above_capacity_throws_exception(): void
    {
        $vehicle = new Vehicle(['type' => VehicleType::ELECTRIC_CAR, 'battery_kwh' => 60]);
        $garage = new Garage(['type' => GarageType::ELECTRIC, 'daily_price' => 40000, 'kwh_price' => 2500]);

        $this->expectException(InvalidArgumentException::class);
        $this->pricing->chargingCost($vehicle, $garage, 61);
    }

    #[Test] // UT-29, total is exactly rent + charging with no extra surcharge
    public function new_rental_total_is_rent_plus_charging_without_surcharge(): void
    {
        $this->assertSame(431500, $this->pricing->newRentalTotal(250000, 181500));
    }

    #[Test] // UT-30, only the added days are charged
    public function extension_cost_five_more_days_is_five_times_daily(): void
    {
        $cost = $this->pricing->extensionCost(25000, Carbon::parse('2026-11-27'), Carbon::parse('2026-12-02'));

        $this->assertSame(125000, $cost);
    }

    #[Test] // UT-31, a full calendar month is charged 25x, leftover days daily
    public function extension_cost_one_month_plus_days_is_monthly_plus_daily(): void
    {
        // Added 1 May - 11 Jun: May (31 days) = 1 month, 11 leftover days charged daily.
        $cost = $this->pricing->extensionCost(10000, Carbon::parse('2026-04-30'), Carbon::parse('2026-06-11'));

        $this->assertSame(25 * 10000 + 11 * 10000, $cost);
    }

    #[Test] // UT-32, 30 days in a 31-day month are still charged daily
    public function extension_cost_30_days_in_may_is_charged_daily(): void
    {
        $cost = $this->pricing->extensionCost(10000, Carbon::parse('2026-04-30'), Carbon::parse('2026-05-30'));

        $this->assertSame(30 * 10000, $cost);
    }

    #[Test] // UT-33 and UT-34, the amount due is never zero or negative
    #[DataProvider('invalidNewEndDates')]
    public function extension_cost_new_date_not_after_old_throws_exception(string $newEnd): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pricing->extensionCost(25000, Carbon::parse('2026-11-27'), Carbon::parse($newEnd));
    }

    public static function invalidNewEndDates(): array
    {
        return ['same date' => ['2026-11-27'], 'earlier date' => ['2026-11-20']];
    }
}
