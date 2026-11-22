<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Login and logout. Tenants not yet approved by an admin cannot sign in.
 */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->validated())) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        $user = Auth::user();

        // Guard clause: a tenant who is pending or rejected is logged straight back out.
        if (! $user->isAdmin() && ! $user->isApproved()) {
            $message = $user->status === UserStatus::REJECTED
                ? 'Pendaftaran Anda ditolak oleh admin.'
                : 'Akun Anda masih menunggu verifikasi admin.';
            Auth::logout();

            return back()->withErrors(['email' => $message])->onlyInput('email');
        }

        $request->session()->regenerate();

        // Always go to the start page of the user's role (an "intended" URL could belong to the other role -> 403).
        return redirect()->route($user->isAdmin() ? 'admin.garages.index' : 'rentals.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
