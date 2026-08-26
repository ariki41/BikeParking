<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use App\Services\ParkingSpotImageService;
use App\Services\ParkingSpotPersistenceService;
use App\ValueObjects\PersistedParkingSpotImages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ParkingSpotPersistenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_delegates_images_and_persists_the_authenticated_creator(): void
    {
        [$user, $postalcode] = $this->createUserAndPostalcode();
        $input = $this->validInput($postalcode);
        $persistedImages = new PersistedParkingSpotImages([], [], []);
        $images = Mockery::mock(ParkingSpotImageService::class);
        $images->shouldReceive('persistConfirmedImages')
            ->once()
            ->with(Mockery::type(ParkingSpot::class), [])
            ->andReturn($persistedImages);
        $images->shouldReceive('replaceParkingSpotImages')
            ->once()
            ->with(Mockery::type(ParkingSpot::class), []);
        $images->shouldReceive('deleteImagePaths')
            ->once()
            ->with([]);

        $parkingSpot = (new ParkingSpotPersistenceService($images))->create($input, $user);

        $this->assertSame($user->id, $parkingSpot->user_id);
        $this->assertSame('保存境界テスト駐輪場', $parkingSpot->name);
        $this->assertSame($postalcode->id, $parkingSpot->postalcode_id);
        $this->assertTrue($parkingSpot->postalcode->is($postalcode));
        $this->assertDatabaseHas('parking_spot_rates', [
            'parking_spot_id' => $parkingSpot->id,
            'rate' => 100,
        ]);
    }

    private function createUserAndPostalcode(): array
    {
        $prefecture = Prefecture::create([
            'name' => '東京都',
            'name_kana' => 'トウキョウト',
        ]);
        $city = City::create([
            'prefecture_id' => $prefecture->id,
            'name' => '千代田区',
            'name_kana' => 'チヨダク',
        ]);
        $postalcode = Postalcode::create([
            'postalcode' => '1000001',
            'city_id' => $city->id,
            'name' => '千代田',
            'name_kana' => 'チヨダ',
        ]);
        $user = User::factory()->create(['prefecture_id' => $prefecture->id]);

        return [$user, $postalcode];
    }

    private function validInput(Postalcode $postalcode): array
    {
        return [
            'name' => '保存境界テスト駐輪場',
            'postalcode' => $postalcode->postalcode,
            'address' => '東京都千代田区千代田1-1',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'opening_time' => '00:00',
            'closing_time' => '00:00',
            'rates' => [[
                'day_type' => '全日',
                'start_time' => '00:00',
                'end_time' => '00:00',
                'unit_minutes' => 30,
                'rate' => 100,
                'free_minutes' => 0,
                'max_rate' => 1200,
            ]],
        ];
    }
}
