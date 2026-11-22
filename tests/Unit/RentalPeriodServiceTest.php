<?php

namespace Tests\Unit;

use App\Enums\RentalType;
use App\Services\RentalPeriodService;
use Carbon\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for rental duration rules (calendar month of the start date), including boundary values.
 */
class RentalPeriodServiceTest extends TestCase
{
    private RentalPeriodService $period;

    protected function setUp(): void
    {
        $this->period = new RentalPeriodService;
    }

    #[Test] // UT-01 to UT-05
    #[DataProvider('monthLengths')]
    public function duration_days_monthly_follows_days_in_start_month(string $start, int $expected): void
    {
        $this->assertSame($expected, $this->period->durationDays(RentalType::MONTHLY, Carbon::parse($start), null));
    }

    public static function monthLengths(): array
    {
        return [
            'January, starting on the 29th (31)' => ['2027-01-29', 31],
            'February 2027 (28)' => ['2027-02-01', 28],
            'February 2028 leap year (29)' => ['2028-02-15', 29],
            'April (30)' => ['2026-04-20', 30],
            'May (31)' => ['2026-05-10', 31],
        ];
    }

    #[Test] // UT-06 to UT-10, boundary values around the April limit of 29 days
    #[DataProvider('aprilDailyBoundaries')]
    public function duration_days_daily_in_april_max_is_29(int $days, bool $valid): void
    {
        if (! $valid) {
            $this->expectException(InvalidArgumentException::class);
        }

        $result = $this->period->durationDays(RentalType::DAILY, Carbon::parse('2026-04-20'), $days);

        $this->assertSame($days, $result);
    }

    public static function aprilDailyBoundaries(): array
    {
        return [
            '0 days (invalid)' => [0, false],
            '1 day (lower bound)' => [1, true],
            '28 days' => [28, true],
            '29 days (upper bound)' => [29, true],
            '30 days (invalid)' => [30, false],
        ];
    }

    #[Test] // UT-11
    public function max_daily_days_in_may_is_30(): void
    {
        $this->assertSame(30, $this->period->maxDailyDays(Carbon::parse('2026-05-01')));
    }

    #[Test] // UT-12
    public function max_daily_days_in_non_leap_february_is_27(): void
    {
        $this->assertSame(27, $this->period->maxDailyDays(Carbon::parse('2027-02-10')));
    }

    #[Test] // UT-13
    public function duration_days_daily_without_days_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->period->durationDays(RentalType::DAILY, Carbon::parse('2026-11-21'), null);
    }

    #[Test] // UT-14, a monthly rental that starts late in the month ends in the next month
    public function monthly_rental_starting_29_january_ends_28_february(): void
    {
        $start = Carbon::parse('2027-01-29');

        $duration = $this->period->durationDays(RentalType::MONTHLY, $start, null);

        $this->assertSame(31, $duration);
        $this->assertSame('2027-02-28', $this->period->endDate($start, $duration)->toDateString());
    }

    #[Test] // UT-15 to UT-17
    #[DataProvider('endDates')]
    public function end_date_is_inclusive_of_start_day(string $start, int $duration, string $expected): void
    {
        $this->assertSame($expected, $this->period->endDate(Carbon::parse($start), $duration)->toDateString());
    }

    public static function endDates(): array
    {
        return [
            '1 Feb + monthly 28 days' => ['2027-02-01', 28, '2027-02-28'],
            '21 Nov + 3 days' => ['2026-11-21', 3, '2026-11-23'],
            '1 day = same day' => ['2026-11-21', 1, '2026-11-21'],
        ];
    }
}
