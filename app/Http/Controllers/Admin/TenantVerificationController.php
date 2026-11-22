<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * New tenant verification. The admin checks the ID-card (KTP) data manually, then approves or rejects.
 */
class TenantVerificationController extends Controller
{
    public function index(): View
    {
        $tenants = User::where('role', UserRole::TENANT);

        return view('admin.tenants.index', [
            'pending' => (clone $tenants)->where('status', UserStatus::PENDING)->oldest()->get(),
            'processed' => (clone $tenants)->where('status', '!=', UserStatus::PENDING)->latest('verified_at')->get(),
        ]);
    }

    public function approve(User $tenant): RedirectResponse
    {
        return $this->decide($tenant, UserStatus::APPROVED, 'diterima');
    }

    public function reject(User $tenant): RedirectResponse
    {
        return $this->decide($tenant, UserStatus::REJECTED, 'ditolak');
    }

    private function decide(User $tenant, UserStatus $status, string $word): RedirectResponse
    {
        if ($tenant->role !== UserRole::TENANT || $tenant->status !== UserStatus::PENDING) {
            return back()->withErrors(['tenant' => 'Pendaftar ini sudah diproses atau bukan penyewa.']);
        }

        $tenant->update(['status' => $status, 'verified_at' => now()]);

        return back()->with('success', "Pendaftaran {$tenant->name} {$word}.");
    }
}
