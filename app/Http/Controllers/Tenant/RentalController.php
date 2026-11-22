<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteRentalRequest;
use App\Http\Requests\StoreRentalRequest;
use App\Models\Rental;
use App\Services\GarageService;
use App\Services\RentalPeriodService;
use App\Services\RentalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Tenant features: my rentals and renting a new garage.
 */
class RentalController extends Controller
{
    public function __construct(
        private RentalService $rentalService,
        private GarageService $garageService,
    ) {}

    public function index(): View
    {
        $this->garageService->syncStatuses(today());

        $rentals = Rental::with(['garage', 'vehicle', 'extensions'])
            ->whereHas('vehicle', fn ($q) => $q->where('user_id', auth()->id()))
            ->latest('start_date')
            ->get();

        return view('tenant.rentals.index', ['rentals' => $rentals, 'today' => today()]);
    }

    public function create(RentalPeriodService $period): View
    {
        return view('tenant.rentals.create', [
            'vehicleTypes' => VehicleType::cases(),
            'rentalTypes' => RentalType::cases(),
            'garageTypes' => GarageType::cases(),
            'today' => today(),
            'maxDailyDays' => $period->maxDailyDays(today()),
        ]);
    }

    /** JSON endpoint for the form: end date, available garages and cost breakdown. */
    public function quote(QuoteRentalRequest $request): JsonResponse
    {
        try {
            return response()->json($this->rentalService->quote($request->user(), $request->validated()));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function store(StoreRentalRequest $request): RedirectResponse
    {
        try {
            $rental = $this->rentalService->createRental(
                $request->user(), $request->validated(), $request->file('payment_proof')
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['rental' => $e->getMessage()])->withInput();
        }

        return redirect()->route('rentals.index')->with(
            'success',
            "Sewa garasi {$rental->garage->code} berhasil disimpan. Total biaya: Rp ".number_format($rental->total_cost, 0, ',', '.')
        );
    }
}
