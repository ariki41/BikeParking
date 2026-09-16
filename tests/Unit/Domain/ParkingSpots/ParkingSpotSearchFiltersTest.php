<?php

namespace Tests\Unit\Domain\ParkingSpots;

use App\Domain\ParkingSpots\ParkingSpotSearchFilters;
use Tests\TestCase;

class ParkingSpotSearchFiltersTest extends TestCase
{
    public function test_it_normalizes_query_values_and_generates_their_labels(): void
    {
        $filters = ParkingSpotSearchFilters::from([
            'capacity' => '3,invalid,1,3',
            'open_24_hours' => 'true',
            'has_free_time' => '1',
            'max_rate' => '1000',
            'exclude_closed' => 'on',
        ], 'over_400cc,invalid,up_to_125cc,over_400cc');

        $this->assertSame([
            'capacity' => [1, 3],
            'open_24_hours' => true,
            'has_free_time' => true,
            'max_rate' => 1000,
            'exclude_closed' => true,
        ], $filters->applied());
        $this->assertSame(['up_to_125cc', 'over_400cc'], $filters->engineDisplacements);
        $this->assertSame([
            'capacity' => '1,3',
            'open_24_hours' => '1',
            'has_free_time' => '1',
            'max_rate' => '1000',
            'exclude_closed' => '1',
        ], $filters->queryParameters());
        $this->assertSame([
            '収容台数: 5台未満',
            '収容台数: 21台以上',
            '排気量: 125cc以下',
            '排気量: 400cc超',
            '24時間営業',
            '無料時間あり',
            '閉鎖済みを除外',
            '最大料金: 1,000円以下',
        ], $filters->labels());
    }

    public function test_it_excludes_invalid_max_rate_without_treating_it_as_a_filter(): void
    {
        $filters = ParkingSpotSearchFilters::from(['max_rate' => '0']);

        $this->assertSame([], $filters->applied());
        $this->assertFalse(ParkingSpotSearchFilters::maxRateIsValid('0'));
        $this->assertFalse(ParkingSpotSearchFilters::maxRateIsValid('invalid'));
    }
}
