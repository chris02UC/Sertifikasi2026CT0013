<?php

namespace App\Services;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Models\Garage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Garage operations: batch creation, price update, deletion, finding available garages, syncing statuses.
 */
class GarageService
{
    public function __construct(private GarageCodeGenerator $codeGenerator) {}

    /**
     * Creates a number of new garages with the same type and price.
     *
     * @param  int|null  $kwhPrice  required for electric-car garages, ignored for other types
     * @return Collection<int, Garage>
     *
     * @throws InvalidArgumentException if a price is invalid or codes run out
     */
    public function createBatch(GarageType $type, int $count, int $dailyPrice, ?int $kwhPrice): Collection
    {
        if ($dailyPrice <= 0) {
            throw new InvalidArgumentException('Harga sewa harian harus lebih dari 0.');
        }
        if ($type === GarageType::ELECTRIC && ($kwhPrice === null || $kwhPrice <= 0)) {
            throw new InvalidArgumentException('Harga isi listrik per kWh wajib untuk garasi mobil listrik.');
        }

        return DB::transaction(function () use ($type, $count, $dailyPrice, $kwhPrice) {
            $codes = $this->codeGenerator->generate($type, $count);

            return collect($codes)->map(fn (string $code) => Garage::create([
                'code' => $code,
                'type' => $type,
                'daily_price' => $dailyPrice,
                'kwh_price' => $type === GarageType::ELECTRIC ? $kwhPrice : null,
                'status' => GarageStatus::AVAILABLE,
            ]));
        });
    }

    /**
     * Changes a garage's prices. Code and type never change (the code is generated).
     * Existing rentals keep their own price snapshot (rentals.daily_price).
     *
     * @param  int|null  $kwhPrice  required for electric-car garages, ignored for other types
     *
     * @throws InvalidArgumentException if a price is invalid
     */
    public function updatePrices(Garage $garage, int $dailyPrice, ?int $kwhPrice): Garage
    {
        if ($dailyPrice <= 0) {
            throw new InvalidArgumentException('Harga sewa harian harus lebih dari 0.');
        }
        if ($garage->isElectric() && ($kwhPrice === null || $kwhPrice <= 0)) {
            throw new InvalidArgumentException('Harga isi listrik per kWh wajib untuk garasi mobil listrik.');
        }

        $garage->update([
            'daily_price' => $dailyPrice,
            'kwh_price' => $garage->isElectric() ? $kwhPrice : null,
        ]);

        return $garage;
    }

    /**
     * Deletes a garage that has never been rented. A garage with rental history is kept
     * (the database foreign key rentals.garage_id is RESTRICT as well).
     *
     * @throws InvalidArgumentException if the garage has rentals
     */
    public function delete(Garage $garage): void
    {
        if ($garage->rentals()->exists()) {
            throw new InvalidArgumentException("Garasi {$garage->code} tidak bisa dihapus karena memiliki riwayat sewa.");
        }

        $garage->delete();
    }

    /**
     * Garages of the given type that are free for the whole date range [start, end].
     *
     * @return Collection<int, Garage>
     */
    public function availableGarages(GarageType $type, Carbon $start, Carbon $end): Collection
    {
        return Garage::where('type', $type)
            ->availableBetween($start, $end)
            ->orderBy('code')
            ->get();
    }

    /**
     * Syncs garage status with rental data: 'used' if the garage has a current
     * or upcoming rental, otherwise 'available'.
     *
     * @return int number of garages that became available again
     */
    public function syncStatuses(Carbon $today): int
    {
        $date = $today->toDateString();

        Garage::where('status', GarageStatus::AVAILABLE)
            ->whereHas('rentals', fn ($q) => $q->whereDate('end_date', '>=', $date))
            ->update(['status' => GarageStatus::USED]);

        return Garage::where('status', GarageStatus::USED)
            ->whereDoesntHave('rentals', fn ($q) => $q->whereDate('end_date', '>=', $date))
            ->update(['status' => GarageStatus::AVAILABLE]);
    }
}
