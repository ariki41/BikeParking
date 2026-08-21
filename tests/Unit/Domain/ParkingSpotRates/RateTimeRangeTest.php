<?php

namespace Tests\Unit\Domain\ParkingSpotRates;

use App\Domain\ParkingSpotRates\RateTimeRange;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RateTimeRangeTest extends TestCase
{
    #[DataProvider('overlapProvider')]
    public function test_it_determines_time_range_overlaps(
        string $leftStart,
        string $leftEnd,
        string $rightStart,
        string $rightEnd,
        bool $expected,
    ): void {
        $left = RateTimeRange::fromTimes($leftStart, $leftEnd);
        $right = RateTimeRange::fromTimes($rightStart, $rightEnd);

        $this->assertSame($expected, $left->overlaps($right));
        $this->assertSame($expected, $right->overlaps($left));
    }

    public static function overlapProvider(): array
    {
        return [
            'same-day overlap' => ['08:00', '12:00', '11:59', '15:00', true],
            'same-day adjacency' => ['08:00', '12:00', '12:00', '18:00', false],
            'one-minute gap' => ['08:00', '11:59', '12:00', '18:00', false],
            'full day' => ['00:00', '00:00', '12:00', '12:01', true],
            'overnight overlap before midnight' => ['22:00', '06:00', '23:00', '23:30', true],
            'overnight overlap after midnight' => ['22:00', '06:00', '05:59', '09:00', true],
            'overnight boundaries are adjacent' => ['22:00', '06:00', '06:00', '22:00', false],
            'midnight boundary is adjacent' => ['18:00', '00:00', '00:00', '06:00', false],
            'two overnight ranges overlap' => ['22:00', '06:00', '23:00', '05:00', true],
        ];
    }

    #[DataProvider('invalidTimeProvider')]
    public function test_it_rejects_invalid_times(string $time): void
    {
        $this->expectException(InvalidArgumentException::class);

        RateTimeRange::fromTimes($time, '12:00');
    }

    public static function invalidTimeProvider(): array
    {
        return [
            'single digit hour' => ['8:00'],
            'hour above range' => ['24:00'],
            'minute above range' => ['23:60'],
            'non-time text' => ['noon'],
        ];
    }
}
