<?php

namespace Tests\Feature;

use App\Models\ParkingSpot;
use App\Models\RetiredUserId;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('登録した駐輪場・料金・画像、投稿したレビュー、更新履歴は退会済みユーザーとして匿名化して残ります。')
            ->assertSee('あなたのお気に入りは削除されます。')
            ->assertSee('同じユーザーIDでは再登録できません。');
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
