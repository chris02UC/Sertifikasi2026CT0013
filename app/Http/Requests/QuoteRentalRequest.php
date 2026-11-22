<?php

namespace App\Http\Requests;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates rental data for the cost quote and the list of available garages.
 * The tenant enters the vehicle details for every rental (spec: "Detail Kendaraan").
 */
class QuoteRentalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'vehicle_type' => ['required', Rule::enum(VehicleType::class)],
            // Plate number is required for every vehicle except bicycles.
            'plate_number' => ['required_unless:vehicle_type,bicycle', 'nullable', 'string', 'max:15'],
            'brand_model' => ['required', 'string', 'max:100'],
            // Battery capacity (kWh) only for electric cars.
            'battery_kwh' => ['required_if:vehicle_type,electric_car', 'nullable', 'numeric', 'min:1', 'max:10000'],
            'rental_type' => ['required', Rule::enum(RentalType::class)],
            'days' => ['required_if:rental_type,daily', 'nullable', 'integer', 'min:1', 'max:30'],
            // Today by default; a future date is allowed, a past date is not.
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'garage_type' => ['required', Rule::enum(GarageType::class)],
        ];
    }
}
