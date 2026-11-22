<?php

use App\Http\Controllers\Admin\GarageController;
use App\Http\Controllers\Admin\RentalExtensionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\TenantVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Tenant\RentalController;
use Illuminate\Support\Facades\Route;

// Home page: redirect based on role.
Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return auth()->user()->isAdmin()
        ? redirect()->route('admin.garages.index')
        : redirect()->route('rentals.index');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Tenant
    Route::middleware('role:tenant')->group(function () {
        Route::get('/rentals', [RentalController::class, 'index'])->name('rentals.index');
        Route::get('/rentals/create', [RentalController::class, 'create'])->name('rentals.create');
        Route::post('/rentals/quote', [RentalController::class, 'quote'])->name('rentals.quote');
        Route::post('/rentals', [RentalController::class, 'store'])->name('rentals.store');
    });

    // Admin
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/garages', [GarageController::class, 'index'])->name('garages.index');
        Route::get('/garages/create', [GarageController::class, 'create'])->name('garages.create');
        Route::post('/garages', [GarageController::class, 'store'])->name('garages.store');
        Route::get('/garages/{garage}/edit', [GarageController::class, 'edit'])->name('garages.edit');
        Route::put('/garages/{garage}', [GarageController::class, 'update'])->name('garages.update');
        Route::delete('/garages/{garage}', [GarageController::class, 'destroy'])->name('garages.destroy');

        Route::get('/tenants', [TenantVerificationController::class, 'index'])->name('tenants.index');
        Route::patch('/tenants/{tenant}/approve', [TenantVerificationController::class, 'approve'])->name('tenants.approve');
        Route::patch('/tenants/{tenant}/reject', [TenantVerificationController::class, 'reject'])->name('tenants.reject');

        Route::get('/extensions', [RentalExtensionController::class, 'index'])->name('extensions.index');
        Route::get('/extensions/{rental}/edit', [RentalExtensionController::class, 'edit'])->name('extensions.edit');
        Route::put('/extensions/{rental}', [RentalExtensionController::class, 'update'])->name('extensions.update');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });
});
