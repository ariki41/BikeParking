<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\InteractsWithParkingSpotRateFixtures;
use Tests\TestCase;

class ParkingSpotRateDayTypeMigrationTest extends TestCase
{
    use InteractsWithParkingSpotRateFixtures;
    use RefreshDatabase;

    public function test_it_normalizes_legacy_time_of_day_categories_without_changing_time_ranges(): void
    {
        [$parkingSpot] = $this->createParkingSpot();
        $daytime = $parkingSpot->rates()->create($this->rateAttributes('昼間', '08:00:00', '18:00:00'));
        $nighttime = $parkingSpot->rates()->create($this->rateAttributes('夜間', '18:00:00', '06:00:00'));

        (require database_path('migrations/2026_09_16_000001_normalize_legacy_rate_time_of_day_categories.php'))->up();

        $this->assertDatabaseHas('parking_spot_rates', [
            'id' => $daytime->id,
            'day_type' => '全日',
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
        ]);
        $this->assertDatabaseHas('parking_spot_rates', [
            'id' => $nighttime->id,
            'day_type' => '全日',
            'start_time' => '18:00:00',
            'end_time' => '06:00:00',
        ]);
    }

    private function rateAttributes(string $dayType, string $startTime, string $endTime): array
    {
        return [
            'day_type' => $dayType,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'unit_minutes' => 30,
            'rate' => 100,
            'free_minutes' => 0,
            'max_rate' => null,
        ];
    }
}
