<?php

namespace Tests\Unit\Domain\ParkingSpotRates;

use App\Domain\ParkingSpotRates\RatePeriod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RatePeriodTest extends TestCase
{
    #[DataProvider('overlapProvider')]
    public function test_it_combines_day_type_and_time_overlap_rules(
        array $leftValues,
        array $rightValues,
        bool $expected,
    ): void {
        $left = RatePeriod::fromValues(...$leftValues);
        $right = RatePeriod::fromValues(...$rightValues);

        $this->assertSame($expected, $left->overlaps($right));
        $this->assertSame($expected, $right->overlaps($left));
    }

    public static function overlapProvider(): array
    {
        return [
            'same day type with overlapping time' => [
                ['平日', '08:00', '12:00'],
                ['平日', '11:00', '15:00'],
                true,
            ],
            'disjoint day types with overlapping time' => [
                ['平日', '08:00', '12:00'],
                ['土日祝', '11:00', '15:00'],
                false,
            ],
            'overlapping day types with adjacent time' => [
                ['昼間', '08:00', '18:00'],
                ['夜間', '18:00', '23:00'],
                false,
            ],
            'all days and overnight overlap' => [
                ['全日', '00:00', '00:00'],
                ['夜間', '22:00', '06:00'],
                true,
            ],
        ];
    }
}
