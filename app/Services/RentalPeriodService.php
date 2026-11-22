<?php

namespace App\Services;

use App\Enums\RentalType;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Rental duration and date rules (based on the calendar month of the start date).
 *
 * - Monthly rental = exactly 1 month; its length = number of days in the start month
 *   (February 28/29, April 30, May 31).
 * - Daily rental = 1 to (days in the start month - 1) days.
 * - The end date is inclusive: end = start + duration - 1 day.
 */
class RentalPeriodService
{
    /** Number of days in the month of the start date. */
    public function daysInStartMonth(Carbon $start): int
    {
        return $start->daysInMonth;
    }

    /** Maximum daily rental length: one day shorter than a month. */
    public function maxDailyDays(Carbon $start): int
    {
        return $this->daysInStartMonth($start) - 1;
    }

    /**
     * Calculates the rental duration in days.
     *
     * @param  RentalType  $type  daily or monthly
     * @param  Carbon  $start  rental start date
     * @param  int|null  $days  number of days (required for daily rentals)
     * @return int duration in days
     *
     * @throws InvalidArgumentException if the daily days are outside 1..maxDailyDays
     */
    public function durationDays(RentalType $type, Carbon $start, ?int $days): int
    {
        if ($type === RentalType::MONTHLY) {
            return $this->daysInStartMonth($start);
        }

        $max = $this->maxDailyDays($start);
        if ($days === null || $days < 1 || $days > $max) {
            throw new InvalidArgumentException("Jumlah hari sewa harian harus 1 sampai {$max} hari.");
        }

        return $days;
    }

    /** End date (inclusive): 1 Feb + 28 days -> 28 Feb. */
    public function endDate(Carbon $start, int $durationDays): Carbon
    {
        return $start->copy()->startOfDay()->addDays($durationDays - 1);
    }
}
