<?php

namespace App\Services;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\RentalExtension;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Garage rental business processes: cost quote, new rental, and admin extension.
 */
class RentalService
{
    public function __construct(
        private PricingService $pricing,
        private RentalPeriodService $period,
        private GarageService $garageService,
    ) {}

    /**
     * Rental quote (used by the form before saving): dates, duration, and the available
     * garages with their cost breakdown.
     *
     * @param  array  $data  validated data from QuoteRentalRequest
     * @return array{start_date: string, end_date: string, duration_days: int, max_daily_days: int, garages: array}
     *
     * @throws InvalidArgumentException if the start date is in the past, the number of days is invalid,
     *                                  or the garage type is unsuitable for the vehicle
     */
    public function quote(User $tenant, array $data): array
    {
        $vehicle = $this->newVehicle($tenant, $data);
        $garageType = GarageType::from($data['garage_type']);
        $this->assertVehicleFitsGarage($vehicle, $garageType);

        [$type, $start, $duration, $end] = $this->resolvePeriod($data);

        // Available garages of the chosen type, each with its cost breakdown.
        $garages = $this->garageService->availableGarages($garageType, $start, $end)
            ->map(function (Garage $garage) use ($type, $duration, $vehicle, $data) {
                $rent = $this->pricing->rentCost($type, $garage->daily_price, $duration);
                $charging = $this->pricing->chargingCost($vehicle, $garage, $this->currentKwh($vehicle, $data));

                return [
                    'id' => $garage->id,
                    'code' => $garage->code,
                    'daily_price' => $garage->daily_price,
                    'kwh_price' => $garage->kwh_price,
                    'rent_cost' => $rent,
                    'charging_cost' => $charging,
                    'total_cost' => $this->pricing->newRentalTotal($rent, $charging),
                ];
            })->values()->all();

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'duration_days' => $duration,
            'max_daily_days' => $this->period->maxDailyDays($start),
            'garages' => $garages,
        ];
    }

    /**
     * Saves a tenant's new rental, then sets the garage status to 'used'.
     *
     * @param  array  $data  validated data from StoreRentalRequest
     *
     * @throws InvalidArgumentException if the start date is in the past, the number of days is invalid,
     *                                  the garage is unsuitable for the vehicle, or it is already booked
     */
    public function createRental(User $tenant, array $data, UploadedFile $proof): Rental
    {
        [$type, $start, $duration, $end] = $this->resolvePeriod($data);

        return DB::transaction(function () use ($tenant, $data, $proof, $type, $start, $duration, $end) {
            // Lock the garage row so two tenants cannot book the same garage at the same time.
            $garage = Garage::whereKey($data['garage_id'])->lockForUpdate()->firstOrFail();

            $vehicle = $this->newVehicle($tenant, $data);
            $this->assertVehicleFitsGarage($vehicle, $garage->type);
            $this->assertGarageFree($garage, $start, $end);
            $vehicle->save();

            $rentCost = $this->pricing->rentCost($type, $garage->daily_price, $duration);
            $currentKwh = $this->currentKwh($vehicle, $data);
            $chargingCost = $this->pricing->chargingCost($vehicle, $garage, $currentKwh);

            $rental = Rental::create([
                'vehicle_id' => $vehicle->id,
                'garage_id' => $garage->id,
                'rental_type' => $type,
                'start_date' => $start,
                'end_date' => $end,
                'duration_days' => $duration,
                'daily_price' => $garage->daily_price,
                'rent_cost' => $rentCost,
                'current_kwh' => $vehicle->type->requiresBattery() ? $currentKwh : null,
                'charging_cost' => $chargingCost,
                'total_cost' => $this->pricing->newRentalTotal($rentCost, $chargingCost),
                'payment_proof_path' => $proof->store('payment-proofs', 'public'),
            ]);

            $garage->update(['status' => GarageStatus::USED]);

            return $rental;
        });
    }

    /**
     * Rentals running on the given date (for the extension feature).
     *
     * @return Collection<int, Rental>
     */
    public function activeRentalsOn(Carbon $date): Collection
    {
        $day = $date->toDateString();

        return Rental::with(['vehicle.owner', 'garage'])
            ->whereDate('start_date', '<=', $day)
            ->whereDate('end_date', '>=', $day)
            ->orderBy('end_date')
            ->get();
    }

    /**
     * Calculates the extension amount due without saving (shown to the admin before uploading proof).
     *
     * @throws InvalidArgumentException if the rental is not running today, the new date is invalid,
     *                                  or the garage is already booked
     */
    public function extensionQuote(Rental $rental, Carbon $newEnd): int
    {
        $this->assertActiveToday($rental);
        $newEnd = $newEnd->copy()->startOfDay();
        $amount = $this->pricing->extensionCost($rental->daily_price, $rental->end_date, $newEnd);
        $this->assertGarageFree($rental->garage, $rental->end_date->copy()->addDay(), $newEnd, ignoreRentalId: $rental->id);

        return $amount;
    }

    /**
     * Rental extension by an admin (the tenant asks outside the system).
     *
     * @throws InvalidArgumentException if the rental is not running today, the new date is not after the old one,
     *                                  or the garage is already booked
     */
    public function extend(Rental $rental, Carbon $newEnd, UploadedFile $proof, User $admin): RentalExtension
    {
        return DB::transaction(function () use ($rental, $newEnd, $proof, $admin) {
            $rental = Rental::whereKey($rental->id)->lockForUpdate()->firstOrFail();
            // Also lock the garage row (the same row createRental locks) so a new booking and
            // an extension of the same garage cannot both pass assertGarageFree at the same time.
            Garage::whereKey($rental->garage_id)->lockForUpdate()->firstOrFail();
            $this->assertActiveToday($rental);
            $oldEnd = $rental->end_date->copy();
            $newEnd = $newEnd->copy()->startOfDay();

            $amountDue = $this->pricing->extensionCost($rental->daily_price, $oldEnd, $newEnd);
            $this->assertGarageFree($rental->garage, $oldEnd->copy()->addDay(), $newEnd, ignoreRentalId: $rental->id);

            $addedDays = (int) $oldEnd->diffInDays($newEnd);

            $extension = $rental->extensions()->create([
                'admin_id' => $admin->id,
                'old_end_date' => $oldEnd,
                'new_end_date' => $newEnd,
                'added_days' => $addedDays,
                'amount_due' => $amountDue,
                'payment_proof_path' => $proof->store('payment-proofs', 'public'),
            ]);

            $rental->update([
                'end_date' => $newEnd,
                'duration_days' => $rental->duration_days + $addedDays,
            ]);

            return $extension;
        });
    }

    /**
     * Start date from the form (today by default, never in the past) -> duration -> end date.
     *
     * @return array{0: RentalType, 1: Carbon, 2: int, 3: Carbon} [type, start, duration, end]
     *
     * @throws InvalidArgumentException if the start date is before today
     */
    private function resolvePeriod(array $data): array
    {
        $type = RentalType::from($data['rental_type']);
        $start = Carbon::parse($data['start_date'])->startOfDay();
        if ($start->lt(today())) {
            throw new InvalidArgumentException('Tanggal mulai sewa tidak boleh sebelum hari ini.');
        }
        $duration = $this->period->durationDays($type, $start, isset($data['days']) ? (int) $data['days'] : null);

        return [$type, $start, $duration, $this->period->endDate($start, $duration)];
    }

    /** The vehicle entered on the form (not saved yet; saved together with the rental). */
    private function newVehicle(User $tenant, array $data): Vehicle
    {
        $type = VehicleType::from($data['vehicle_type']);

        return new Vehicle([
            'user_id' => $tenant->id,
            'type' => $type,
            'plate_number' => $type->requiresPlate() ? strtoupper(trim($data['plate_number'])) : null,
            'brand_model' => $data['brand_model'],
            'battery_kwh' => $type->requiresBattery() ? (float) $data['battery_kwh'] : null,
        ]);
    }

    /** Battery charge left from the form (electric cars only; 0 for other vehicles). */
    private function currentKwh(Vehicle $vehicle, array $data): float
    {
        return $vehicle->type->requiresBattery() ? (float) $data['current_kwh'] : 0.0;
    }

    private function assertVehicleFitsGarage(Vehicle $vehicle, GarageType $garageType): void
    {
        if (! $vehicle->type->canUse($garageType)) {
            throw new InvalidArgumentException("{$vehicle->type->label()} tidak boleh ditempatkan di {$garageType->label()}.");
        }
    }

    /** Only a rental that is running today can be extended (spec: "garasi yang sedang disewa pada hari itu"). */
    private function assertActiveToday(Rental $rental): void
    {
        if (! $rental->isActiveOn(today())) {
            throw new InvalidArgumentException("Sewa garasi {$rental->garage->code} tidak sedang berjalan hari ini.");
        }
    }

    private function assertGarageFree(Garage $garage, Carbon $start, Carbon $end, ?int $ignoreRentalId = null): void
    {
        $clash = $garage->rentals()
            ->when($ignoreRentalId, fn ($q) => $q->whereKeyNot($ignoreRentalId))
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->exists();

        if ($clash) {
            throw new InvalidArgumentException("Garasi {$garage->code} sudah disewa pada tanggal tersebut.");
        }
    }
}
