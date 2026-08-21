<?php

namespace Tests\Unit\Domain\ParkingSpotRates;

use App\Domain\ParkingSpotRates\RateDayType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RateDayTypeTest extends TestCase
{
    #[DataProvider('overlapProvider')]
    public function test_it_determines_day_type_overlaps(string $left, string $right, bool $expected): void
    {
        $this->assertSame(
            $expected,
            RateDayType::from($left)->overlaps(RateDayType::from($right)),
        );
    }

    public static function overlapProvider(): array
    {
        return [
            'same weekday' => ['平日', '平日', true],
            'weekday and holiday' => ['平日', '土日祝', false],
            'all days and weekday' => ['全日', '平日', true],
            'daytime and holiday' => ['昼間', '土日祝', true],
            'nighttime and weekday' => ['夜間', '平日', true],
            'daytime and nighttime' => ['昼間', '夜間', true],
        ];
    }

    public function test_it_exposes_all_supported_input_values(): void
    {
        $this->assertSame(
            ['全日', '平日', '土日祝', '昼間', '夜間'],
            RateDayType::values(),
        );
    }
}
