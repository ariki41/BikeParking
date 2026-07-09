<?php

namespace Database\Seeders;

use App\Models\ParkingSpot;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            //PrefectureSeeder::class,
            UserSeeder::class,
            ParkingSpotSeeder::class,
        ]);

        User::factory(100)->create();
        //ParkingSpot::factory(1000)->create();
    }
}
