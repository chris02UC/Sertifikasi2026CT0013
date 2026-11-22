<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the format of tenant registration data. The ID-card (KTP) check itself is done manually by an admin.
 */
class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nik' => ['required', 'digits:16', 'unique:users,nik'],
            'name' => ['required', 'string', 'max:100'],
            // Digits or +, 8 to 15 characters.
            'phone' => ['required', 'regex:/^[0-9+]{8,15}$/'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
