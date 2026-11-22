<?php

namespace App\Models;

use App\Enums\RentalType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rental transaction of one garage for one vehicle.
 *
 * All money values are in rupiah (integer). total_cost = rent_cost + charging_cost.
 * daily_price is a snapshot of the garage price when the rental was created.
 * Relationships: belongs to one Vehicle and one Garage; has many RentalExtensions (composition).
 *
 * @author  Sewa Garasi Team
 *
 * @version 1.0
 */
class Rental extends Model
{
    protected $fillable = [
        'vehicle_id', 'garage_id', 'rental_type', 'start_date', 'end_date', 'duration_days',
        'daily_price', 'rent_cost', 'charging_cost', 'total_cost', 'payment_proof_path',
    ];

    protected function casts(): array
    {
        return [
            'rental_type' => RentalType::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_days' => 'integer',
            'daily_price' => 'integer',
            'rent_cost' => 'integer',
            'charging_cost' => 'integer',
            'total_cost' => 'integer',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(RentalExtension::class);
    }

    /** True if $date is between start_date and end_date (inclusive). */
    public function isActiveOn(Carbon $date): bool
    {
        return $date->copy()->startOfDay()->betweenIncluded($this->start_date, $this->end_date);
    }
}
