<?php

namespace Database\Seeders;

use App\Models\Prefecture;
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
            PrefectureSeeder::class,
            UserSeeder::class,
            ParkingSpotSeeder::class,
        ]);

        $prefectureIds = Prefecture::query()->pluck('id');

        User::factory(100)->create([
            'prefecture_id' => fn () => $prefectureIds->random(),
        ]);

        $this->call(JapanParkingSpotSeeder::class);

        $this->call(ReviewSeeder::class);
    }
}
