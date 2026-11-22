<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates changing a garage's prices. The price per kWh is only required for electric-car garages.
 */
class UpdateGarageRequest extends FormRequest
{
    public function rules(): array
    {
        $electric = $this->route('garage')->isElectric();

        return [
            'daily_price' => ['required', 'integer', 'min:1'],
            'kwh_price' => [Rule::requiredIf($electric), 'nullable', 'integer', 'min:1'],
        ];
    }
}
