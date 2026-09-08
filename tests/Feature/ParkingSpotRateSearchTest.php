<?php

namespace Tests\Feature;

use App\Domain\ParkingSpots\EngineDisplacementClass;
use App\Livewire\ParkingSpots;
use App\Models\ParkingSpot;
use App\Models\ParkingSpotRates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\InteractsWithParkingSpotRateFixtures;
use Tests\TestCase;

class ParkingSpotRateSearchTest extends TestCase
{
    use InteractsWithParkingSpotRateFixtures;
    use RefreshDatabase;

    public function test_home_displays_uploaded_image_and_fallback_image(): void
    {
        [$parkingSpot, $user] = $this->createParkingSpot();
        $parkingSpot->forceFill(['image_path' => 'parking-spots/home.jpg'])->save();

        ParkingSpot::forceCreate([
            'user_id' => $user->id,
            'name' => '画像なし駐輪場',
            'postalcode_id' => $parkingSpot->postalcode_id,
            'address' => '東京都千代田区千代田1-9',
            'longitude' => 139.759000,
            'latitude' => 35.689000,
            'capacity' => 2,
            'max_displacement_class' => EngineDisplacementClass::UpTo400cc->value,
            'opening_time' => '00:00:00',
            'closing_time' => '00:00:00',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('/storage/parking-spots/home.jpg');
        $response->assertSee('/images/noimage.jpg');
    }

    public function test_home_displays_first_registered_rate_as_representative_with_remaining_count(): void
    {
        [$parkingSpot] = $this->createParkingSpot();

        ParkingSpotRates::create([
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '平日',
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'unit_minutes' => 30,
            'rate' => 100,
            'free_minutes' => 30,
            'max_rate' => 1200,
        ]);
        ParkingSpotRates::create([
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '土日祝',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'unit_minutes' => 60,
            'rate' => 50,
            'free_minutes' => 0,
            'max_rate' => 500,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('代表料金');
        $response->assertSee('最初の30分無料 / 以降30分 100円 / 最大 1,200円');
        $response->assertSee('ほか1件の料金帯');
        $response->assertSee('平日');
        $response->assertSee('08:00 ～ 20:00');
        $response->assertDontSee('1時間 50円 / 最大 500円');
    }

    public function test_home_displays_rate_placeholder_without_rates(): void
    {
        $this->createParkingSpot();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('新着のバイク駐輪場')
            ->assertSee('代表料金')
            ->assertSee('料金未登録');
    }

    public function test_favorites_index_displays_rate_summary(): void
    {
        [$parkingSpot, $user] = $this->createParkingSpot();
        $user->favorites()->create([
            'parking_spot_id' => $parkingSpot->id,
        ]);

        ParkingSpotRates::create([
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '平日',
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'unit_minutes' => 30,
            'rate' => 100,
            'free_minutes' => 0,
            'max_rate' => 1200,
        ]);

        $this->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('代表料金')
            ->assertSee('30分 100円 / 最大 1,200円')
            ->assertSee('平日')
            ->assertSee('08:00 ～ 20:00');
    }

    public function test_livewire_parking_spots_list_displays_rate_summary(): void
    {
        [$parkingSpot] = $this->createParkingSpot();

        ParkingSpotRates::create([
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '全日',
            'start_time' => '00:00:00',
            'end_time' => '00:00:00',
            'unit_minutes' => 30,
            'rate' => 100,
            'free_minutes' => 0,
            'max_rate' => null,
        ]);
        ParkingSpotRates::create([
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '夜間',
            'start_time' => '20:00:00',
            'end_time' => '08:00:00',
            'unit_minutes' => 60,
            'rate' => 200,
            'free_minutes' => 0,
            'max_rate' => 800,
        ]);

        Livewire::test(ParkingSpots::class)
            ->call('updateBounds', [
                'south' => 35.0,
                'north' => 36.0,
                'west' => 139.0,
                'east' => 140.0,
            ])
            ->assertSee('バイク駐輪場を探す')
            ->assertSee('代表料金')
            ->assertSee('30分 100円 / 最大料金なし')
            ->assertSee('ほか1件の料金帯')
            ->assertSee('00:00 ～ 24:00')
            ->assertDontSee('1時間 200円 / 最大 800円');
    }

    public function test_livewire_parking_spots_list_uses_image_url_accessor(): void
    {
        [$parkingSpot] = $this->createParkingSpot();
        $parkingSpot->forceFill(['image_path' => 'parking-spots/list.jpg'])->save();

        Livewire::test(ParkingSpots::class)
            ->set('spots', collect([
                $parkingSpot->fresh()
                    ->load('representativeRate')
                    ->loadCount('rates'),
            ]))
            ->assertSee('/storage/parking-spots/list.jpg');
    }
}
