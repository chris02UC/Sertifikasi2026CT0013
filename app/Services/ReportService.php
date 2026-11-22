<?php

namespace App\Services;

use App\Enums\GarageType;
use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Garage rental reports.
 *
 * Rentals are grouped by their start date.
 * Revenue = rental total (rent + charging) + all of its extension payments.
 */
class ReportService
{
    /**
     * Rental count and revenue per month of the given year (always 12 rows).
     *
     * @return Collection<int, array{label: string, rentals: int, revenue: int}>
     */
    public function monthlyInYear(int $year): Collection
    {
        $rentals = $this->rentalsBetween(Carbon::create($year, 1, 1), Carbon::create($year, 12, 31));

        return collect(range(1, 12))->map(function (int $month) use ($rentals, $year) {
            $items = $rentals->filter(fn (Rental $r) => $r->start_date->month === $month);

            return $this->row(Carbon::create($year, $month, 1)->translatedFormat('F'), $items);
        });
    }

    /**
     * Rental count and revenue per day of the given month.
     *
     * @return Collection<int, array{label: string, rentals: int, revenue: int}>
     */
    public function dailyInMonth(int $year, int $month): Collection
    {
        $first = Carbon::create($year, $month, 1);
        $rentals = $this->rentalsBetween($first, $first->copy()->endOfMonth());

        return collect(range(1, $first->daysInMonth))->map(function (int $day) use ($rentals) {
            $items = $rentals->filter(fn (Rental $r) => $r->start_date->day === $day);

            return $this->row((string) $day, $items);
        });
    }

    /**
     * Summary per garage type (all data).
     *
     * @return Collection<int, array{label: string, rentals: int, revenue: int}>
     */
    public function byGarageType(): Collection
    {
        $rentals = Rental::with('garage')->withSum('extensions', 'amount_due')->get();

        return collect(GarageType::cases())->map(fn (GarageType $type) => $this->row(
            $type->label(),
            $rentals->filter(fn (Rental $r) => $r->garage->type === $type),
        ));
    }

    /**
     * Summary per tenant (the tenant is found through the vehicle), sorted by highest revenue.
     *
     * @return Collection<int, array{label: string, rentals: int, revenue: int}>
     */
    public function byTenant(): Collection
    {
        return Rental::with('vehicle.owner')->withSum('extensions', 'amount_due')->get()
            ->groupBy(fn (Rental $r) => $r->vehicle->owner->id)
            ->map(fn (Collection $items) => $this->row($items->first()->vehicle->owner->name, $items))
            ->sortByDesc('revenue')
            ->values();
    }

    private function rentalsBetween(Carbon $from, Carbon $to): Collection
    {
        return Rental::withSum('extensions', 'amount_due')
            ->whereDate('start_date', '>=', $from->toDateString())
            ->whereDate('start_date', '<=', $to->toDateString())
            ->get();
    }

    /** @return array{label: string, rentals: int, revenue: int} */
    private function row(string $label, Collection $rentals): array
    {
        return [
            'label' => $label,
            'rentals' => $rentals->count(),
            'revenue' => (int) $rentals->sum(fn (Rental $r) => $r->total_cost + (int) $r->extensions_sum_amount_due),
        ];
    }
}
