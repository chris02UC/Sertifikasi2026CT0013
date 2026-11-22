<?php

namespace Tests\Unit;

use App\Enums\GarageType;
use App\Services\GarageCodeGenerator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for garage code generation (pure logic, no database).
 */
class GarageCodeGeneratorTest extends TestCase
{
    private GarageCodeGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new GarageCodeGenerator;
    }

    #[Test] // UT-35
    public function build_codes_first_bicycle_garage_is_s001(): void
    {
        $this->assertSame(['S001', 'S002', 'S003'], $this->generator->buildCodes(GarageType::BICYCLE, 0, 3));
    }

    #[Test] // UT-36
    public function build_codes_tenth_car_garage_is_c010(): void
    {
        $this->assertSame(['C010'], $this->generator->buildCodes(GarageType::CAR, 9, 1));
    }

    #[Test] // UT-37
    public function build_codes_electric_garage_uses_prefix_e(): void
    {
        $this->assertSame(['E006', 'E007'], $this->generator->buildCodes(GarageType::ELECTRIC, 5, 2));
    }

    #[Test] // UT-38, boundary: exactly 999
    public function build_codes_up_to_999_is_valid(): void
    {
        $codes = $this->generator->buildCodes(GarageType::CAR, 0, 999);

        $this->assertCount(999, $codes);
        $this->assertSame('C999', end($codes));
    }

    #[Test] // UT-39, boundary: 999 + 1
    public function build_codes_beyond_999_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->generator->buildCodes(GarageType::CAR, 998, 2);
    }

    #[Test] // UT-40
    public function build_codes_zero_count_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->generator->buildCodes(GarageType::BICYCLE, 0, 0);
    }
}
