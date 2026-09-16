<?php

namespace Tests\Unit\Domain\ParkingSpotRates;

use App\Domain\ParkingSpotRates\RateDisplay;
use App\Models\ParkingSpotRates;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RateDisplayTest extends TestCase
{
    #[DataProvider('rateProvider')]
    public function test_it_formats_rate_display_values(array $rate, array $expected): void
    {
        $display = RateDisplay::fromArray($rate);

        $this->assertSame($expected['timeRangeLabel'], $display->timeRangeLabel);
        $this->assertSame($expected['baseRateLabel'], $display->baseRateLabel);
        $this->assertSame($expected['maxRateLabel'], $display->maxRateLabel);
        $this->assertSame($expected['rateLabel'], $display->rateLabel);
    }

    public static function rateProvider(): array
    {
        return [
            'rate without a maximum' => [
                ['day_type' => '全日', 'start_time' => '08:00', 'end_time' => '20:00', 'unit_minutes' => 30, 'rate' => 100, 'free_minutes' => 0, 'max_rate' => null],
                ['timeRangeLabel' => '08:00 ～ 20:00', 'baseRateLabel' => '30分 100円', 'maxRateLabel' => '最大料金なし', 'rateLabel' => '30分 100円 / 最大料金なし'],
            ],
            'rate with free time and a maximum' => [
                ['day_type' => '平日', 'start_time' => '08:00', 'end_time' => '20:00', 'unit_minutes' => 60, 'rate' => 200, 'free_minutes' => 60, 'max_rate' => 1200],
                ['timeRangeLabel' => '08:00 ～ 20:00', 'baseRateLabel' => '最初の1時間無料 / 以降1時間 200円', 'maxRateLabel' => '1,200円', 'rateLabel' => '最初の1時間無料 / 以降1時間 200円 / 最大 1,200円'],
            ],
            'overnight rate' => [
                ['day_type' => '全日', 'start_time' => '22:00', 'end_time' => '06:00', 'unit_minutes' => 60, 'rate' => 200, 'free_minutes' => 0, 'max_rate' => 800],
                ['timeRangeLabel' => '22:00 ～ 翌06:00', 'baseRateLabel' => '1時間 200円', 'maxRateLabel' => '800円', 'rateLabel' => '1時間 200円 / 最大 800円'],
            ],
            'full day rate' => [
                ['day_type' => '全日', 'start_time' => '00:00', 'end_time' => '00:00', 'unit_minutes' => 1440, 'rate' => 500, 'free_minutes' => 0, 'max_rate' => 500],
                ['timeRangeLabel' => '00:00 ～ 24:00', 'baseRateLabel' => '24時間 500円', 'maxRateLabel' => '500円', 'rateLabel' => '24時間 500円 / 最大 500円'],
            ],
            'free rate' => [
                ['day_type' => '全日', 'start_time' => '00:00', 'end_time' => '00:00', 'unit_minutes' => 30, 'rate' => 0, 'free_minutes' => 0, 'max_rate' => null],
                ['timeRangeLabel' => '00:00 ～ 24:00', 'baseRateLabel' => '無料', 'maxRateLabel' => '最大料金なし', 'rateLabel' => '無料'],
            ],
        ];
    }

    public function test_it_formats_a_persisted_rate_with_the_same_rules(): void
    {
        $rate = new ParkingSpotRates([
            'day_type' => '全日',
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
            'unit_minutes' => 60,
            'rate' => 200,
            'free_minutes' => 0,
            'max_rate' => 800,
        ]);

        $display = RateDisplay::fromModel($rate);

        $this->assertSame('22:00 ～ 翌06:00', $display->timeRangeLabel);
        $this->assertSame('1時間 200円 / 最大 800円', $display->rateLabel);
        $this->assertSame($display->rateLabel, $rate->rate_label);
    }

    public function test_it_describes_max_rate_conditions(): void
    {
        $display = RateDisplay::fromArray([
            'day_type' => '全日', 'start_time' => '00:00', 'end_time' => '00:00',
            'unit_minutes' => 30, 'rate' => 100, 'free_minutes' => 0, 'max_rate' => 800,
            'max_rate_period' => 'until_midnight', 'max_rate_repeats' => true,
        ]);

        $this->assertSame('800円', $display->maxRateLabel);
        $this->assertSame('当日24時まで・繰り返し適用', $display->maxRateConditionLabel);
        $this->assertSame('30分 100円 / 最大 800円（当日24時まで・繰り返し適用）', $display->rateLabel);
    }
}
