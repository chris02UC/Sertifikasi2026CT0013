<?php

namespace App\Http\Requests;

/**
 * Validates saving a new rental: quote data + the chosen garage + proof of payment.
 */
class StoreRentalRequest extends QuoteRentalRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'garage_id' => ['required', 'integer', 'exists:garages,id'],
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);
    }
}
