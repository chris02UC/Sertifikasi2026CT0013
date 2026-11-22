<?php

namespace App\Models;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Garage master data.
 *
 * Key attributes: unique code (S001/C001/E001), daily_price in rupiah per day,
 * kwh_price (rupiah per kWh) for electric-car garages only.
 * Relationship: one Garage has many Rentals (1:N).
 *
 * @author  Christopher
 *
 * @version 1.0
 */
class Garage extends Model
{
    protected $fillable = ['code', 'type', 'daily_price', 'kwh_price', 'status'];

    protected function casts(): array
    {
        return [
            'type' => GarageType::class,
            'status' => GarageStatus::class,
            'daily_price' => 'integer',
            'kwh_price' => 'integer',
        ];
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function isElectric(): bool
    {
        return $this->type === GarageType::ELECTRIC;
    }

    /**
     * Garages with no rental overlapping the date range [start, end].
     * Two ranges overlap when rental_start <= end AND rental_end >= start.
     */
    public function scopeAvailableBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereDoesntHave('rentals', function (Builder $rental) use ($start, $end) {
            $rental->whereDate('start_date', '<=', $end->toDateString())
                ->whereDate('end_date', '>=', $start->toDateString());
        });
    }
}
