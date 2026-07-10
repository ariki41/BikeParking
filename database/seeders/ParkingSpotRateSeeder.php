<?php

namespace Database\Seeders;

use App\Models\ParkingSpot;
use App\Models\ParkingSpotRates;
use Illuminate\Database\Seeder;

class ParkingSpotRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ParkingSpotRates::truncate();

        $ratePatterns = [
            [
                ['day_type' => '平日', 'start_time' => '08:00:00', 'end_time' => '20:00:00', 'unit_minutes' => 30, 'rate' => 100, 'free_minutes' => 0, 'max_rate' => 1200],
                ['day_type' => '土日祝', 'start_time' => '08:00:00', 'end_time' => '20:00:00', 'unit_minutes' => 30, 'rate' => 150, 'free_minutes' => 0, 'max_rate' => 1800],
            ],
            [
                ['day_type' => '全日', 'start_time' => '00:00:00', 'end_time' => '00:00:00', 'unit_minutes' => 60, 'rate' => 100, 'free_minutes' => 0, 'max_rate' => 1000],
            ],
            [
                ['day_type' => '昼間', 'start_time' => '07:00:00', 'end_time' => '19:00:00', 'unit_minutes' => 12, 'rate' => 300, 'free_minutes' => 0, 'max_rate' => 900],
                ['day_type' => '夜間', 'start_time' => '19:00:00', 'end_time' => '07:00:00', 'unit_minutes' => 60, 'rate' => 200, 'free_minutes' => 0, 'max_rate' => 500],
            ],
            [
                ['day_type' => '平日', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'unit_minutes' => 30, 'rate' => 100, 'free_minutes' => 30, 'max_rate' => 0],
                ['day_type' => '土日祝', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'unit_minutes' => 30, 'rate' => 100, 'free_minutes' => 0, 'max_rate' => 600],
            ],
            [
                ['day_type' => '全日', 'start_time' => '06:00:00', 'end_time' => '22:00:00', 'unit_minutes' => 15, 'rate' => 50, 'free_minutes' => 0, 'max_rate' => 0],
                ['day_type' => '夜間', 'start_time' => '22:00:00', 'end_time' => '06:00:00', 'unit_minutes' => 60, 'rate' => 100, 'free_minutes' => 0, 'max_rate' => 700],
            ],
        ];

        ParkingSpot::query()
            ->select('id')
            ->chunkById(100, function ($parkingSpots) use ($ratePatterns): void {
                foreach ($parkingSpots as $parkingSpot) {
                    $rates = $ratePatterns[($parkingSpot->id - 1) % count($ratePatterns)];

                    foreach ($rates as $rate) {
                        ParkingSpotRates::create([
                            'parking_spot_id' => $parkingSpot->id,
                            ...$rate,
                        ]);
                    }
                }
            });
    }
}
