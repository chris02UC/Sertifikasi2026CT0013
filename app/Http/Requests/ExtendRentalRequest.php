<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates an admin rental extension: the new end date must be after the current end date.
 */
class ExtendRentalRequest extends FormRequest
{
    public function rules(): array
    {
        $currentEnd = $this->route('rental')->end_date->toDateString();

        return [
            'new_end_date' => ['required', 'date', 'after:'.$currentEnd],
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }
}
