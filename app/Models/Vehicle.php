<?php

namespace App\Models;

use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A tenant's vehicle.
 *
 * plate_number is empty for bicycles; battery_kwh is only set for electric cars.
 * Relationships: belongs to one User; has many Rentals.
 *
 * @author  Sewa Garasi Team
 *
 * @version 1.0
 */
class Vehicle extends Model
{
    protected $fillable = ['user_id', 'type', 'plate_number', 'brand_model', 'battery_kwh'];

    protected function casts(): array
    {
        return [
            'type' => VehicleType::class,
            'battery_kwh' => 'float',
        ];
    }

    /** The tenant who owns this vehicle. */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /** Short display text, e.g. "Mobil Listrik - L 1234 AB (Hyundai Ioniq 5)". */
    public function displayName(): string
    {
        $plate = $this->plate_number ? " - {$this->plate_number}" : '';

        return "{$this->type->label()}{$plate} ({$this->brand_model})";
    }
}
