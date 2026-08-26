<?php

namespace Tests\Feature;

use App\Livewire\ParkingSpots;
use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class ParkingSpotReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_submit_and_view_review(): void
    {
        [$parkingSpot, $user] = $this->createParkingSpot();

        $this->actingAs($user)
            ->post(route('reviews.store', $parkingSpot), [
                'rating' => 4,
                'comment' => '駅に近く、屋根もあって使いやすいです。',
            ])
            ->assertRedirect(route('parking_spot.show', $parkingSpot));

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'parking_spot_id' => $parkingSpot->id,
            'rating' => 4,
            'comment' => '駅に近く、屋根もあって使いやすいです。',
        ]);

        $this->get(route('parking_spot.show', $parkingSpot))
            ->assertOk()
            ->assertSee('評価・レビュー')
            ->assertSee('4.0')
            ->assertSee('1件')
            ->assertSee($user->name)
            ->assertSee('駅に近く、屋根もあって使いやすいです。');
    }

    public function test_review_requires_valid_rating_and_comment(): void
    {
        [$parkingSpot, $user] = $this->createParkingSpot();

        $this->actingAs($user)
            ->from(route('parking_spot.show', $parkingSpot))
            ->post(route('reviews.store', $parkingSpot), [
                'rating' => 6,
                'comment' => '',
            ])
            ->assertRedirect(route('parking_spot.show', $parkingSpot))
            ->assertSessionHasErrors(['rating', 'comment']);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_guest_cannot_submit_review(): void
    {
        [$parkingSpot] = $this->createParkingSpot();

        $this->post(route('reviews.store', $parkingSpot), [
            'rating' => 5,
            'comment' => '投稿できないレビュー',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_can_update_only_their_own_review(): void
    {
        [$parkingSpot, $user] = $this->createParkingSpot();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->post(route('reviews.store', $parkingSpot), [
                'rating' => 2,
                'comment' => '最初の評価',
            ]);

        $review = Review::sole();
        $this->assertFalse(Gate::forUser($otherUser)->allows('update', $review));

        $this->actingAs($user)
            ->post(route('reviews.store', $parkingSpot), [
                'rating' => 5,
                'comment' => '更新後の評価',
            ])
            ->assertRedirect(route('parking_spot.show', $parkingSpot));

        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => '更新後の評価',
        ]);
    }

    public function test_average_rating_and_count_are_displayed_on_main_lists(): void
    {
        [$parkingSpot, $firstUser] = $this->createParkingSpot();
        $secondUser = User::factory()->create();

        Review::forceCreate([
            'user_id' => $firstUser->id,
            'parking_spot_id' => $parkingSpot->id,
            'rating' => 3,
            'comment' => '標準的です。',
        ]);
        Review::forceCreate([
            'user_id' => $secondUser->id,
            'parking_spot_id' => $parkingSpot->id,
            'rating' => 4,
            'comment' => '便利でした。',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('3.5')
            ->assertSee('2件');

        Livewire::test(ParkingSpots::class)
            ->call('updateBounds', [
                'south' => 35.0,
                'north' => 36.0,
                'west' => 139.0,
                'east' => 140.0,
            ])
            ->assertSee('3.5')
            ->assertSee('2件');
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

        $user = User::factory()->create(['prefecture_id' => $prefecture->id]);

        $parkingSpot = ParkingSpot::forceCreate([
            'user_id' => $user->id,
            'name' => 'レビューテスト駐輪場',
            'postalcode_id' => $postalcode->id,
            'address' => '東京都千代田区千代田1-1',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'opening_time' => '00:00:00',
            'closing_time' => '00:00:00',
        ]);

        return [$parkingSpot, $user];
    }
}
