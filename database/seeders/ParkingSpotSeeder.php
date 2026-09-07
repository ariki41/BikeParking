<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ParkingSpotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sourcePath = config('parking_spot.sample_data.postalcode_csv_path');

        if (! is_string($sourcePath) || ! is_readable($sourcePath)) {
            $this->command?->warn('Skipped parking-spot samples because the optional coordinate CSV is unavailable.');

            return;
        }

        $this->call(JapanParkingSpotSeeder::class);
    }
}
