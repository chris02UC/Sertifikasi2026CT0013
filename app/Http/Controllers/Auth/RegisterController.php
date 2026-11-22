<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * New tenant registration. The account stays 'pending' until an admin approves it.
 */
class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        User::create([
            ...$request->validated(),
            'role' => UserRole::TENANT,
            'status' => UserStatus::PENDING,
        ]);

        return redirect()->route('login')
            ->with('success', 'Pendaftaran berhasil. Silakan tunggu verifikasi KTP oleh admin sebelum login.');
    }
}
