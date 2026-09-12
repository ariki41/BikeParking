<?php

namespace Tests\Feature;

use App\Models\ParkingSpot;
use App\Models\RetiredUserId;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertSee('アカウント設定')
            ->assertSee(route('profile.reviews'), false)
            ->assertSee('登録した駐輪場・料金・画像、投稿したレビュー、更新履歴は退会済みユーザーとして匿名化して残ります。')
            ->assertSee('あなたのお気に入りは削除されます。')
            ->assertSee('同じユーザーIDでは再登録できません。');
    }

    public function test_profile_displays_only_the_authenticated_users_reviews_in_updated_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $olderParkingSpot = ParkingSpot::factory()->create(['name' => '古いレビューの駐輪場']);
        $newerParkingSpot = ParkingSpot::factory()->create(['name' => '新しいレビューの駐輪場']);
        $otherParkingSpot = ParkingSpot::factory()->create(['name' => '他ユーザーの駐輪場']);

        Review::forceCreate([
            'user_id' => $user->id,
            'parking_spot_id' => $olderParkingSpot->id,
            'rating' => 3,
            'comment' => '古いレビューコメント',
            'updated_at' => Carbon::parse('2026-09-01 10:00:00'),
        ]);
        Review::forceCreate([
            'user_id' => $user->id,
            'parking_spot_id' => $newerParkingSpot->id,
            'rating' => 5,
            'comment' => '新しいレビューコメント',
            'updated_at' => Carbon::parse('2026-09-02 10:00:00'),
        ]);
        Review::forceCreate([
            'user_id' => $otherUser->id,
            'parking_spot_id' => $otherParkingSpot->id,
            'rating' => 1,
            'comment' => '他ユーザーのレビューコメント',
        ]);

        $this->actingAs($user)
            ->get(route('profile.reviews'))
            ->assertOk()
            ->assertSee('投稿したレビュー')
            ->assertSee('全2件')
            ->assertSeeInOrder(['新しいレビューの駐輪場', '新しいレビューコメント', '古いレビューの駐輪場', '古いレビューコメント'])
            ->assertSee(route('parking_spot.show', $newerParkingSpot).'#reviews', false)
            ->assertDontSee('他ユーザーの駐輪場')
            ->assertDontSee('他ユーザーのレビューコメント');
    }

    public function test_profile_paginates_reviews(): void
    {
        $user = User::factory()->create();
        $baseTime = Carbon::parse('2026-09-01 10:00:00');

        foreach (range(1, 12) as $position) {
            $parkingSpot = ParkingSpot::factory()->create(['name' => sprintf('ページネーション駐輪場-%02d', $position)]);

            Review::forceCreate([
                'user_id' => $user->id,
                'parking_spot_id' => $parkingSpot->id,
                'rating' => 4,
                'comment' => sprintf('ページネーションレビュー-%02d', $position),
                'updated_at' => $baseTime->copy()->addMinutes($position),
            ]);
        }

        $this->actingAs($user)
            ->get(route('profile.reviews'))
            ->assertOk()
            ->assertSee('全12件')
            ->assertSee(route('profile.reviews', ['page' => 2]), false)
            ->assertSee('ページネーションレビュー-12')
            ->assertDontSee('ページネーションレビュー-02');

        $this->actingAs($user)
            ->get(route('profile.reviews', ['page' => 2]))
            ->assertOk()
            ->assertSeeInOrder(['ページネーションレビュー-02', 'ページネーションレビュー-01'])
            ->assertDontSee('ページネーションレビュー-03');
    }

    public function test_profile_displays_an_empty_state_when_the_user_has_not_posted_reviews(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.reviews'))
            ->assertOk()
            ->assertSee('投稿したレビューはまだありません。')
            ->assertSee('駐輪場の詳細ページから評価・レビューを投稿できます。');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_account_deletion_preserves_anonymized_content_and_removes_personal_favorites(): void
    {
        $departingUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownedParkingSpot = ParkingSpot::factory()->for($departingUser)->create();
        $retainedParkingSpot = ParkingSpot::factory()->for($otherUser)->create();

        $rate = $ownedParkingSpot->rates()->create([
            'day_type' => '全日',
            'start_time' => '00:00:00',
            'end_time' => '00:00:00',
            'unit_minutes' => 30,
            'rate' => 100,
            'free_minutes' => 0,
            'max_rate' => null,
        ]);
        $image = $ownedParkingSpot->images()->create([
            'path' => 'parking-spots/test.webp',
            'position' => 0,
        ]);
        $otherFavorite = $otherUser->favorites()->create(['parking_spot_id' => $ownedParkingSpot->id]);
        $otherReview = $otherUser->reviews()->make([
            'rating' => 5,
            'comment' => '使いやすいです。',
        ]);
        $otherReview->parkingSpot()->associate($ownedParkingSpot);
        $otherReview->save();
        $departingUser->favorites()->create(['parking_spot_id' => $retainedParkingSpot->id]);
        $review = $departingUser->reviews()->make([
            'rating' => 4,
            'comment' => '便利でした。',
        ]);
        $review->parkingSpot()->associate($retainedParkingSpot);
        $review->save();
        $history = $retainedParkingSpot->updateHistories()->create([
            'user_id' => $departingUser->id,
            'changes' => ['name' => ['before' => '変更前', 'after' => '変更後']],
        ]);

        $response = $this
            ->actingAs($departingUser)
            ->delete('/profile', ['password' => 'password']);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $departingUser->id]);
        $this->assertDatabaseHas('retired_user_ids', [
            'user_id_hash' => RetiredUserId::hashFor($departingUser->user_id),
        ]);
        $this->assertDatabaseHas('parking_spots', [
            'id' => $ownedParkingSpot->id,
            'user_id' => null,
        ]);
        $this->assertDatabaseHas('parking_spot_rates', ['id' => $rate->id]);
        $this->assertDatabaseHas('parking_spot_images', ['id' => $image->id]);
        $this->assertDatabaseHas('favorites', ['id' => $otherFavorite->id]);
        $this->assertDatabaseHas('reviews', ['id' => $otherReview->id]);
        $this->assertDatabaseMissing('favorites', ['user_id' => $departingUser->id]);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => null,
        ]);
        $this->assertDatabaseHas('parking_spot_update_histories', [
            'id' => $history->id,
            'user_id' => null,
        ]);
        $this->assertDatabaseHas('parking_spots', ['id' => $retainedParkingSpot->id]);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
