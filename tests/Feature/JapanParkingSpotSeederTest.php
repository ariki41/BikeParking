<?php

namespace Tests\Feature;

use App\Domain\ParkingSpots\EngineDisplacementClass;
use App\Models\ParkingSpot;
use App\Models\Prefecture;
use App\Models\User;
use Database\Seeders\JapanParkingSpotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class JapanParkingSpotSeederTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $temporaryPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryPaths as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    public function test_it_creates_nationwide_parking_spots_from_postalcode_coordinate_csv(): void
    {
        $locations = [
            ['北海道', '札幌市', '北一条西', 43.061037, 141.341425, 1_000_000],
            ['東京都', '千代田区', '千代田', 35.681236, 139.767125, 2_000_000],
            ['沖縄県', '那覇市', '泉崎', 26.212401, 127.680932, 3_000_000],
        ];
        $sourcePath = $this->createSourceCsv($locations, 4);
        $prefectures = [];

        foreach ($locations as [$name]) {
            $prefectures[$name] = Prefecture::query()->create([
                'name' => $name,
                'name_kana' => '',
            ]);
        }

        foreach ($prefectures as $prefecture) {
            User::factory()->create(['prefecture_id' => $prefecture->id]);
        }

        config()->set('parking_spot.sample_data.postalcode_csv_path', $sourcePath);
        config()->set('parking_spot.sample_data.parking_spot_count', 12);
        config()->set('parking_spot.sample_data.insert_chunk_size', 5);
        config()->set('parking_spot.sample_data.location_buffer_per_prefecture', 0);

        $this->seed(JapanParkingSpotSeeder::class);

        $this->assertDatabaseCount('parking_spots', 12);
        $this->assertGreaterThan(0, DB::table('parking_spot_rates')->count());
        $this->assertSame(
            collect(array_fill_keys(EngineDisplacementClass::values(), 3))->sortKeys()->all(),
            DB::table('parking_spots')
                ->selectRaw('max_displacement_class, COUNT(*) as aggregate')
                ->groupBy('max_displacement_class')
                ->orderBy('max_displacement_class')
                ->pluck('aggregate', 'max_displacement_class')
                ->map(fn (int $count): int => $count)
                ->sortKeys()
                ->all(),
        );

        $parkingSpotCountsByPrefecture = DB::table('parking_spots')
            ->join('postalcodes', 'parking_spots.postalcode_id', '=', 'postalcodes.id')
            ->join('cities', 'postalcodes.city_id', '=', 'cities.id')
            ->join('prefectures', 'cities.prefecture_id', '=', 'prefectures.id')
            ->selectRaw('prefectures.name, COUNT(*) as parking_spot_count')
            ->groupBy('prefectures.name')
            ->pluck('parking_spot_count', 'name');

        $this->assertSame(3, $parkingSpotCountsByPrefecture->count());
        $this->assertLessThanOrEqual(
            1,
            $parkingSpotCountsByPrefecture->max() - $parkingSpotCountsByPrefecture->min(),
        );

        $coordinatesByPostalcode = [];

        foreach ($locations as [$prefecture, $city, $town, $latitude, $longitude, $start]) {
            foreach (range(0, 3) as $index) {
                $coordinatesByPostalcode[sprintf('%07d', $start + $index)] = compact(
                    'prefecture',
                    'city',
                    'town',
                    'latitude',
                    'longitude',
                );
            }
        }

        ParkingSpot::query()
            ->with('postalcode')
            ->get()
            ->each(function (ParkingSpot $parkingSpot) use ($coordinatesByPostalcode): void {
                $location = $coordinatesByPostalcode[$parkingSpot->postalcode->postalcode];

                $this->assertStringStartsWith(
                    $location['prefecture'].$location['city'].$location['town'],
                    $parkingSpot->address,
                );
                $this->assertEqualsWithDelta($location['longitude'], $parkingSpot->longitude, 0.006001);
                $this->assertEqualsWithDelta($location['latitude'], $parkingSpot->latitude, 0.006001);
            });
    }

    public function test_it_requires_a_readable_postalcode_coordinate_csv(): void
    {
        $prefecture = Prefecture::query()->create([
            'name' => '東京都',
            'name_kana' => 'トウキョウト',
        ]);
        User::factory()->create(['prefecture_id' => $prefecture->id]);
        config()->set('parking_spot.sample_data.postalcode_csv_path', '/tmp/does-not-exist-postalcode.csv');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('requires a readable postal-code coordinate CSV');

        $this->seed(JapanParkingSpotSeeder::class);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string, 3: float, 4: float, 5: int}>  $locations
     */
    private function createSourceCsv(array $locations, int $locationsPerPrefecture): string
    {
        $path = tempnam(sys_get_temp_dir(), 'japan-parking-seeder-');
        $this->assertNotFalse($path);
        $this->temporaryPaths[] = $path;
        $rows = [];

        foreach ($locations as [$prefecture, $city, $town, $latitude, $longitude, $start]) {
            foreach (range(0, $locationsPerPrefecture - 1) as $index) {
                $rows[] = implode(',', [
                    sprintf('%07d', $start + $index),
                    $prefecture,
                    $city,
                    $town,
                    number_format($latitude + ($index / 100_000), 6, '.', ''),
                    number_format($longitude + ($index / 100_000), 6, '.', ''),
                ]);
            }
        }

        $this->assertNotFalse(file_put_contents($path, implode("\n", $rows)."\n"));

        return $path;
    }
}
