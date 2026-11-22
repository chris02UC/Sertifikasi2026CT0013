<?php

namespace App\Services;

use App\Enums\GarageType;
use App\Models\Garage;
use InvalidArgumentException;

/**
 * Generates garage codes automatically: type prefix + 3 sequential digits per type.
 * Example: the first bicycle garage is S001, the tenth car garage is C010.
 */
class GarageCodeGenerator
{
    /** Highest number per garage type (3 digits). */
    public const MAX_PER_TYPE = 999;

    /**
     * Creates new codes that continue from the last number in the database.
     *
     * @return string[]
     *
     * @throws InvalidArgumentException if the count is invalid or exceeds 999 per type
     */
    public function generate(GarageType $type, int $count): array
    {
        return $this->buildCodes($type, $this->lastNumber($type), $count);
    }

    /**
     * Pure logic (no database) so it is easy to unit test.
     *
     * @param  int  $lastNumber  last number already used (0 if none)
     * @param  int  $count  number of codes to create (1..999)
     * @return string[]
     */
    public function buildCodes(GarageType $type, int $lastNumber, int $count): array
    {
        if ($count < 1 || $count > self::MAX_PER_TYPE) {
            throw new InvalidArgumentException('Jumlah garasi harus 1 sampai '.self::MAX_PER_TYPE.'.');
        }
        if ($lastNumber + $count > self::MAX_PER_TYPE) {
            $remaining = self::MAX_PER_TYPE - $lastNumber;
            throw new InvalidArgumentException("Kode {$type->label()} tersisa {$remaining}, tidak cukup untuk {$count} garasi.");
        }

        $codes = [];
        for ($number = $lastNumber + 1; $number <= $lastNumber + $count; $number++) {
            $codes[] = $this->format($type, $number);
        }

        return $codes;
    }

    /** Highest number already used for this type, e.g. C010 -> 10. */
    private function lastNumber(GarageType $type): int
    {
        $lastCode = Garage::where('type', $type)->max('code');

        return $lastCode ? (int) substr($lastCode, 1) : 0;
    }

    private function format(GarageType $type, int $number): string
    {
        return $type->codePrefix().str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }
}
