<?php

namespace Tests\Unit\Domain\ParkingSpotRates;

use App\Domain\ParkingSpotRates\RateConflictDetector;
use App\Domain\ParkingSpotRates\RatePeriod;
use PHPUnit\Framework\TestCase;

class RateConflictDetectorTest extends TestCase
{
    public function test_it_returns_every_conflicting_pair_with_original_indexes(): void
    {
        $periods = [
            2 => RatePeriod::fromValues('全日', '00:00', '00:00'),
            5 => RatePeriod::fromValues('平日', '09:00', '18:00'),
            8 => RatePeriod::fromValues('土日祝', '10:00', '17:00'),
        ];

        $this->assertSame([
            ['left' => 2, 'right' => 5],
            ['left' => 2, 'right' => 8],
        ], (new RateConflictDetector)->detect($periods));
    }

    public function test_it_returns_no_conflicts_for_adjacent_periods(): void
    {
        $periods = [
            RatePeriod::fromValues('平日', '08:00', '12:00'),
            RatePeriod::fromValues('平日', '12:00', '18:00'),
        ];

        $this->assertSame([], (new RateConflictDetector)->detect($periods));
    }
}
