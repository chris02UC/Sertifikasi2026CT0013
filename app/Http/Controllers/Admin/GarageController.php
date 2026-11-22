<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GarageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGarageBatchRequest;
use App\Http\Requests\UpdateGarageRequest;
use App\Models\Garage;
use App\Services\GarageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Admin feature: garage CRUD. List, create in batches, change prices, delete.
 */
class GarageController extends Controller
{
    public function __construct(private GarageService $garageService) {}

    public function index(): View
    {
        // Sync 'used' / 'available' with the rental dates before showing the list.
        $this->garageService->syncStatuses(today());

        $garages = Garage::orderBy('type')->orderBy('code')->paginate(30);

        return view('admin.garages.index', compact('garages'));
    }

    public function create(): View
    {
        return view('admin.garages.create', ['garageTypes' => GarageType::cases()]);
    }

    public function store(StoreGarageBatchRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $garages = $this->garageService->createBatch(
                GarageType::from($data['type']), (int) $data['count'], (int) $data['daily_price'],
                isset($data['kwh_price']) ? (int) $data['kwh_price'] : null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['count' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.garages.index')->with(
            'success',
            "{$garages->count()} garasi dibuat: {$garages->first()->code} s/d {$garages->last()->code}."
        );
    }

    public function edit(Garage $garage): View
    {
        return view('admin.garages.edit', compact('garage'));
    }

    public function update(UpdateGarageRequest $request, Garage $garage): RedirectResponse
    {
        $data = $request->validated();

        try {
            $this->garageService->updatePrices(
                $garage, (int) $data['daily_price'], isset($data['kwh_price']) ? (int) $data['kwh_price'] : null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['daily_price' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.garages.index')->with('success', "Harga garasi {$garage->code} diperbarui.");
    }

    public function destroy(Garage $garage): RedirectResponse
    {
        try {
            $this->garageService->delete($garage);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['garage' => $e->getMessage()]);
        }

        return redirect()->route('admin.garages.index')->with('success', "Garasi {$garage->code} dihapus.");
    }
}
