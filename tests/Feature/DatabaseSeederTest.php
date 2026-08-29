<?php

namespace Tests\Feature;

use App\Models\ParkingSpot;
use App\Models\Prefecture;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_users_with_the_existing_prefectures(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertFalse(Schema::hasTable('postalcode_lat_lons'));
        $this->assertNotNull(ParkingSpot::factory()->make()->postalcode_id);
        $this->assertSame(48, Prefecture::query()->count());
        $this->assertSame(100, User::query()->count());
        $this->assertDatabaseCount('parking_spots', 10_000);
        $this->assertGreaterThan(0, DB::table('parking_spot_rates')->count());
        $this->assertGreaterThan(1, DB::table('parking_spots')->distinct()->count('capacity'));
        $this->assertGreaterThan(1, DB::table('parking_spots')->distinct()->count('opening_time'));
        $this->assertGreaterThan(1, DB::table('parking_spot_rates')->distinct()->count('rate'));
        $this->assertSame(
            0,
            User::query()
                ->whereNotIn('prefecture_id', Prefecture::query()->select('id'))
                ->count(),
        );
        $this->assertSame(
            0,
            DB::table('parking_spots')
                ->join('postalcodes', 'parking_spots.postalcode_id', '=', 'postalcodes.id')
                ->join('cities', 'postalcodes.city_id', '=', 'cities.id')
                ->join('prefectures', 'cities.prefecture_id', '=', 'prefectures.id')
                ->where('prefectures.name', '海外')
                ->count(),
        );

        $parkingSpotCountsByPrefecture = DB::table('parking_spots')
            ->join('postalcodes', 'parking_spots.postalcode_id', '=', 'postalcodes.id')
            ->join('cities', 'postalcodes.city_id', '=', 'cities.id')
            ->join('prefectures', 'cities.prefecture_id', '=', 'prefectures.id')
            ->selectRaw('prefectures.name, COUNT(*) as parking_spot_count')
            ->groupBy('prefectures.name')
            ->pluck('parking_spot_count', 'name');

        $this->assertCount(47, $parkingSpotCountsByPrefecture);
        $this->assertLessThanOrEqual(
            1,
            $parkingSpotCountsByPrefecture->max() - $parkingSpotCountsByPrefecture->min(),
        );
    }
}
