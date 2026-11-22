<?php

namespace App\Services;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Models\Garage;
use App\Models\Vehicle;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * All rental cost calculations. Money values are in rupiah (integer).
 */
class PricingService
{
    /** Monthly rental price = 25 x the daily price. */
    public const MONTHLY_MULTIPLIER = 25;

    /**
     * Garage rent (excluding the charging cost).
     *
     * @param  RentalType  $type  daily or monthly
     * @param  int  $dailyPrice  garage daily price (> 0)
     * @param  int  $days  number of days (used for daily rentals, >= 1)
     * @return int rent cost
     *
     * @throws InvalidArgumentException if the price or number of days is invalid
     */
    public function rentCost(RentalType $type, int $dailyPrice, int $days): int
    {
        if ($dailyPrice <= 0 || $days < 1) {
            throw new InvalidArgumentException('Harga harian dan jumlah hari harus lebih dari 0.');
        }

        return $type === RentalType::MONTHLY
            ? self::MONTHLY_MULTIPLIER * $dailyPrice
            : $days * $dailyPrice;
    }

    /**
     * Cost of charging the battery to full: (capacity - charge left) kWh x price per kWh.
     * Only applies to an electric car parked in an electric-car garage.
     *
     * @param  float  $currentKwh  battery charge left when the car arrives (0 = empty, capacity = full)
     * @return int charging cost (0 if not applicable or the battery is already full)
     *
     * @throws InvalidArgumentException if the charge left is below 0 or above the battery capacity
     */
    public function chargingCost(Vehicle $vehicle, Garage $garage, float $currentKwh): int
    {
        if ($vehicle->type !== VehicleType::ELECTRIC_CAR || $garage->type !== GarageType::ELECTRIC) {
            return 0;
        }

        $capacity = (float) $vehicle->battery_kwh;
        if ($currentKwh < 0 || $currentKwh > $capacity) {
            throw new InvalidArgumentException('Sisa listrik harus antara 0 dan kapasitas baterai.');
        }

        return (int) round(($capacity - $currentKwh) * ($garage->kwh_price ?? 0));
    }

    /**
     * New rental total = rent + charging cost. No other surcharge is added.
     */
    public function newRentalTotal(int $rentCost, int $chargingCost): int
    {
        return $rentCost + $chargingCost;
    }

    /**
     * Remaining amount due for an extension's added days (oldEnd+1 .. newEnd).
     *
     * The added days are split starting from the day after the old end date. Each full calendar
     * month (length = days in the month where that segment starts) costs 25 x the daily price;
     * leftover days are charged at the daily price.
     *
     * @param  int  $dailyPrice  garage daily price (> 0)
     * @param  Carbon  $oldEnd  current end date
     * @param  Carbon  $newEnd  new end date (must be after $oldEnd)
     * @return int remaining amount due (always > 0)
     *
     * @throws InvalidArgumentException if the new date is not after the old date
     */
    public function extensionCost(int $dailyPrice, Carbon $oldEnd, Carbon $newEnd): int
    {
        if ($dailyPrice <= 0) {
            throw new InvalidArgumentException('Harga harian harus lebih dari 0.');
        }
        if ($newEnd->copy()->startOfDay()->lte($oldEnd->copy()->startOfDay())) {
            throw new InvalidArgumentException('Tanggal selesai baru harus setelah tanggal selesai lama.');
        }

        $cursor = $oldEnd->copy()->startOfDay()->addDay();
        $remaining = (int) $oldEnd->copy()->startOfDay()->diffInDays($newEnd->copy()->startOfDay());
        $cost = 0;

        while ($remaining > 0) {
            $monthLength = $cursor->daysInMonth;
            if ($remaining >= $monthLength) {
                $cost += self::MONTHLY_MULTIPLIER * $dailyPrice;
                $remaining -= $monthLength;
                $cursor->addDays($monthLength);
            } else {
                $cost += $remaining * $dailyPrice;
                $remaining = 0;
            }
        }

        return $cost;
    }
}
