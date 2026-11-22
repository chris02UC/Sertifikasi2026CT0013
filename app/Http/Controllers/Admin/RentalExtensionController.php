<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExtendRentalRequest;
use App\Models\Rental;
use App\Services\RentalService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Extend a garage rental. The tenant asks outside the system (e.g. WhatsApp); the admin enters it.
 */
class RentalExtensionController extends Controller
{
    public function __construct(private RentalService $rentalService) {}

    /** Garages that are rented today. */
    public function index(): View
    {
        return view('admin.extensions.index', [
            'rentals' => $this->rentalService->activeRentalsOn(today()),
            'today' => today(),
        ]);
    }

    /** Extension form. When a new end date is given, the remaining amount due is calculated and shown. */
    public function edit(Request $request, Rental $rental): View
    {
        $rental->load(['vehicle.owner', 'garage', 'extensions.admin']);
        $newEnd = $request->query('new_end_date');
        $amountDue = null;
        $error = null;

        if ($newEnd) {
            try {
                $amountDue = $this->rentalService->extensionQuote($rental, Carbon::parse($newEnd));
            } catch (InvalidArgumentException $e) {
                $error = $e->getMessage();
            }
        }

        return view('admin.extensions.edit', compact('rental', 'newEnd', 'amountDue', 'error'));
    }

    public function update(ExtendRentalRequest $request, Rental $rental): RedirectResponse
    {
        try {
            $extension = $this->rentalService->extend(
                $rental, Carbon::parse($request->validated('new_end_date')), $request->file('payment_proof'), $request->user()
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['new_end_date' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.extensions.index')->with(
            'success',
            "Sewa garasi {$rental->garage->code} diperpanjang {$extension->added_days} hari. Sisa biaya dibayar: Rp "
                .number_format($extension->amount_due, 0, ',', '.')
        );
    }
}
