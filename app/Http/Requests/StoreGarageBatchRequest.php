<?php

namespace App\Http\Requests;

use App\Enums\GarageType;
use App\Services\GarageCodeGenerator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates batch garage creation by an admin.
 */
class StoreGarageBatchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(GarageType::class)],
            'count' => ['required', 'integer', 'min:1', 'max:'.GarageCodeGenerator::MAX_PER_TYPE],
            'daily_price' => ['required', 'integer', 'min:1'],
            'kwh_price' => ['required_if:type,electric', 'nullable', 'integer', 'min:1'],
        ];
    }
}
