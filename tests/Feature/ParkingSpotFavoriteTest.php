<?php

namespace Tests\Feature;

use App\Livewire\ParkingSpots;
use App\Models\City;
use App\Models\Favorite;
use App\Models\ParkingSpot;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ParkingSpotFavoriteTest extends TestCase
{
    use RefreshDatabase;

    private Prefecture $prefecture;

    private Postalcode $postalcode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prefecture = Prefecture::create([
            'name' => '東京都',
            'name_kana' => 'トウキョウト',
        ]);

        $city = City::create([
            'prefecture_id' => $this->prefecture->id,
            'name' => '千代田区',
            'name_kana' => 'チヨダク',
        ]);

        $this->postalcode = Postalcode::create([
            'postalcode' => '1000001',
            'city_id' => $city->id,
            'name' => '千代田',
            'name_kana' => 'チヨダ',
        ]);
    }

    public function test_authenticated_user_can_add_a_favorite_without_creating_duplicates(): void
    {
        $user = $this->createUser();
        $parkingSpot = $this->createParkingSpot('お気に入り追加テスト駐輪場', $user);

        $this->actingAs($user)
            ->from(route('parking_spot.show', $parkingSpot))
            ->post(route('favorites.store', $parkingSpot))
            ->assertRedirect(route('parking_spot.show', $parkingSpot))
            ->assertSessionHas('favorite_success', 'お気に入りに追加しました。');

        $this->from(route('parking_spot.show', $parkingSpot))
            ->post(route('favorites.store', $parkingSpot))
            ->assertRedirect(route('parking_spot.show', $parkingSpot));

        $this->assertDatabaseCount('favorites', 1);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'parking_spot_id' => $parkingSpot->id,
        ]);

        $this->get(route('parking_spot.show', $parkingSpot))
            ->assertOk()
            ->assertSee('お気に入り解除')
            ->assertSee('(1件)');
    }

    public function test_user_can_remove_only_their_own_favorite(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();
        $parkingSpot = $this->createParkingSpot('お気に入り解除テスト駐輪場', $user);

        Favorite::forceCreate([
            'user_id' => $user->id,
            'parking_spot_id' => $parkingSpot->id,
        ]);
        Favorite::forceCreate([
            'user_id' => $otherUser->id,
            'parking_spot_id' => $parkingSpot->id,
        ]);

        $this->actingAs($user)
            ->from(route('parking_spot.show', $parkingSpot))
            ->delete(route('favorites.destroy', $parkingSpot))
            ->assertRedirect(route('parking_spot.show', $parkingSpot))
            ->assertSessionHas('favorite_success', 'お気に入りを解除しました。');

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'parking_spot_id' => $parkingSpot->id,
        ]);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $otherUser->id,
            'parking_spot_id' => $parkingSpot->id,
        ]);
    }

    public function test_favorites_index_lists_only_the_authenticated_users_favorites_and_count(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();
        $favoriteParkingSpot = $this->createParkingSpot('自分のお気に入り駐輪場', $user);
        $otherParkingSpot = $this->createParkingSpot('他人だけのお気に入り駐輪場', $otherUser);

        Favorite::forceCreate([
            'user_id' => $user->id,
            'parking_spot_id' => $favoriteParkingSpot->id,
        ]);
        Favorite::forceCreate([
            'user_id' => $otherUser->id,
            'parking_spot_id' => $otherParkingSpot->id,
        ]);

        $this->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('お気に入り 1件')
            ->assertSee('お気に入り (1)')
            ->assertSee($favoriteParkingSpot->name)
            ->assertDontSee($otherParkingSpot->name);
    }

    public function test_home_and_map_lists_offer_favorite_controls(): void
    {
        $user = $this->createUser();
        $favoriteParkingSpot = $this->createParkingSpot('登録済みお気に入り駐輪場', $user);
        $otherParkingSpot = $this->createParkingSpot('未登録お気に入り駐輪場', $user);

        Favorite::forceCreate([
            'user_id' => $user->id,
            'parking_spot_id' => $favoriteParkingSpot->id,
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('favorites.destroy', $favoriteParkingSpot), false)
            ->assertSee(route('favorites.store', $otherParkingSpot), false)
            ->assertSee('解除')
            ->assertSee('追加');

        Livewire::test(ParkingSpots::class)
            ->call('updateBounds', [
                'south' => 35.0,
                'north' => 36.0,
                'west' => 139.0,
                'east' => 140.0,
            ])
            ->assertSee(route('favorites.destroy', $favoriteParkingSpot), false)
            ->assertSee(route('favorites.store', $otherParkingSpot), false)
            ->assertSee('解除')
            ->assertSee('追加');
    }

    public function test_guest_cannot_access_or_change_favorites(): void
    {
        $owner = $this->createUser();
        $parkingSpot = $this->createParkingSpot('ゲスト操作テスト駐輪場', $owner);

        $this->get(route('favorites.index'))->assertRedirect(route('login'));
        $this->post(route('favorites.store', $parkingSpot))->assertRedirect(route('login'));
        $this->delete(route('favorites.destroy', $parkingSpot))->assertRedirect(route('login'));

        $this->assertDatabaseCount('favorites', 0);
    }

    private function createUser(): User
    {
        return User::factory()->create([
            'prefecture_id' => $this->prefecture->id,
        ]);
    }

    private function createParkingSpot(string $name, User $owner): ParkingSpot
    {
        return ParkingSpot::forceCreate([
            'user_id' => $owner->id,
            'name' => $name,
            'postalcode' => $this->postalcode->id,
            'address' => '東京都千代田区千代田1-1',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'opening_time' => '00:00:00',
            'closing_time' => '00:00:00',
        ]);
    }
}
