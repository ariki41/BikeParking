<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\ParkingSpotRates;
use App\Models\ParkingSpotUpdateHistory;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParkingSpotAuthorizationHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_edit_parking_spot(): void
    {
        [$parkingSpot, $owner] = $this->createParkingSpot();

        $this->actingAs($owner)
            ->get(route('parking_spot.edit', $parkingSpot->id))
            ->assertOk();
    }

    public function test_authenticated_non_owner_can_edit_parking_spot(): void
    {
        [$parkingSpot, , , $prefecture] = $this->createParkingSpot();
        $collaborator = $this->createUser($prefecture, 'collaborator');

        $this->actingAs($collaborator)
            ->get(route('parking_spot.edit', $parkingSpot->id))
            ->assertOk();
    }

    public function test_guest_cannot_edit_or_update_parking_spot(): void
    {
        [$parkingSpot, , $postalcode] = $this->createParkingSpot();

        $this->get(route('parking_spot.edit', $parkingSpot->id))
            ->assertRedirect(route('login'));

        $this->withSession([
            'edit_parking_spot_form' => $this->updateInput($parkingSpot, $postalcode),
        ])->post(route('parking_spot.update'))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('parking_spot_update_histories', 0);
    }

    public function test_update_records_actor_and_changed_values(): void
    {
        [$parkingSpot, , $postalcode, $prefecture] = $this->createParkingSpot();
        $collaborator = $this->createUser($prefecture, 'history-user');

        ParkingSpotRates::create([
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '全日',
            'start_time' => '00:00',
            'end_time' => '00:00',
            'unit_minutes' => 30,
            'rate' => 100,
            'free_minutes' => 0,
            'max_rate' => 1000,
        ]);

        $input = $this->updateInput($parkingSpot, $postalcode, [
            'name' => '共同編集後の駐輪場',
            'capacity' => 2,
            'rates' => [[
                'day_type' => '平日',
                'start_time' => '08:00',
                'end_time' => '20:00',
                'unit_minutes' => 60,
                'rate' => 200,
                'free_minutes' => 30,
                'max_rate' => 1500,
            ]],
        ]);

        $this->actingAs($collaborator)
            ->withSession(['edit_parking_spot_form' => $input])
            ->post(route('parking_spot.update'))
            ->assertRedirect(route('home'));

        $history = ParkingSpotUpdateHistory::sole();

        $this->assertSame($parkingSpot->id, $history->parking_spot_id);
        $this->assertSame($collaborator->id, $history->user_id);
        $this->assertSame('料金表示テスト駐輪場', $history->changes['name']['before']);
        $this->assertSame('共同編集後の駐輪場', $history->changes['name']['after']);
        $this->assertSame(1, $history->changes['capacity']['before']);
        $this->assertSame(2, $history->changes['capacity']['after']);
        $this->assertSame('全日', $history->changes['rates']['before'][0]['day_type']);
        $this->assertSame('平日', $history->changes['rates']['after'][0]['day_type']);
        $this->assertNotNull($history->created_at);

        $this->actingAs($collaborator)
            ->get(route('parking_spot.show', $parkingSpot->id))
            ->assertOk()
            ->assertSee('更新履歴')
            ->assertSee($collaborator->name)
            ->assertSee('駐輪場名')
            ->assertSee('収容台数')
            ->assertSee('料金');
    }

    private function createParkingSpot(): array
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

        $owner = $this->createUser($prefecture, 'owner');

        $parkingSpot = ParkingSpot::forceCreate([
            'user_id' => $owner->id,
            'name' => '料金表示テスト駐輪場',
            'postalcode' => $postalcode->id,
            'address' => '東京都千代田区千代田1-1',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'opening_time' => '00:00:00',
            'closing_time' => '00:00:00',
        ]);

        return [$parkingSpot, $owner, $postalcode, $prefecture];
    }

    private function createUser(Prefecture $prefecture, string $userId): User
    {
        return User::create([
            'user_id' => $userId,
            'name' => $userId,
            'password' => Hash::make('password'),
            'prefecture_id' => $prefecture->id,
        ]);
    }

    private function updateInput(ParkingSpot $parkingSpot, Postalcode $postalcode, array $overrides = []): array
    {
        return array_replace([
            'id' => $parkingSpot->id,
            'name' => $parkingSpot->name,
            'postalcode' => $postalcode->postalcode,
            'address' => $parkingSpot->address,
            'longitude' => $parkingSpot->longitude,
            'latitude' => $parkingSpot->latitude,
            'capacity' => $parkingSpot->capacity,
            'image_path' => $parkingSpot->image_path,
            'opening_time' => '00:00',
            'closing_time' => '00:00',
            'rates' => [[
                'day_type' => '全日',
                'start_time' => '00:00',
                'end_time' => '00:00',
                'unit_minutes' => 30,
                'rate' => 100,
                'free_minutes' => 0,
                'max_rate' => 1000,
            ]],
        ], $overrides);
    }
}
