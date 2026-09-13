<?php

namespace Tests\Feature;

use App\Livewire\ParkingSpots;
use App\Models\City;
use App\Models\Favorite;
use App\Models\ParkingSpot;
use App\Models\ParkingSpotDeletionRequest;
use App\Models\ParkingSpotImage;
use App\Models\ParkingSpotRates;
use App\Models\ParkingSpotReport;
use App\Models\ParkingSpotUpdateHistory;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ParkingSpotModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_report_a_parking_spot_or_its_history(): void
    {
        [$spot, $prefecture] = $this->parkingSpot();
        $history = ParkingSpotUpdateHistory::create(['parking_spot_id' => $spot->id, 'user_id' => $spot->user_id, 'changes' => []]);
        $reporter = User::factory()->create(['prefecture_id' => $prefecture->id]);

        $this->actingAs($reporter)->post(route('parking_spot.reports.store', $spot), ['reason' => '内容が不正確です。', 'parking_spot_update_history_id' => $history->id])
            ->assertRedirect(route('parking_spot.show', $spot));

        $this->assertDatabaseHas('parking_spot_reports', ['parking_spot_id' => $spot->id, 'parking_spot_update_history_id' => $history->id, 'user_id' => $reporter->id, 'reason' => '内容が不正確です。', 'status' => 'pending']);

        $this->actingAs($reporter)->get(route('parking_spot.reports.create', $spot))
            ->assertOk()
            ->assertSee('駐輪場を通報');
    }

    public function test_report_cannot_target_another_spots_history(): void
    {
        [$spot, $prefecture] = $this->parkingSpot();
        [$other] = $this->parkingSpot($prefecture);
        $history = ParkingSpotUpdateHistory::create(['parking_spot_id' => $other->id, 'user_id' => $other->user_id, 'changes' => []]);

        $this->actingAs(User::factory()->create(['prefecture_id' => $prefecture->id]))
            ->from(route('parking_spot.show', $spot))
            ->post(route('parking_spot.reports.store', $spot), ['reason' => '不正な対象です。', 'parking_spot_update_history_id' => $history->id])
            ->assertRedirect(route('parking_spot.show', $spot))
            ->assertSessionHasErrors('parking_spot_update_history_id');
    }

    public function test_non_admin_cannot_access_moderation_operations(): void
    {
        [$spot, $prefecture] = $this->parkingSpot();
        $history = ParkingSpotUpdateHistory::create(['parking_spot_id' => $spot->id, 'user_id' => $spot->user_id, 'changes' => []]);
        $user = User::factory()->create(['prefecture_id' => $prefecture->id]);

        $this->actingAs($user)->get(route('admin.parking_spot_reports.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.parking_spots.hide', $spot), ['moderation_reason' => '確認済みです。'])->assertForbidden();
        $this->actingAs($user)->post(route('admin.parking_spots.histories.restore', [$spot, $history]), ['moderation_reason' => '確認済みです。'])->assertForbidden();
    }

    public function test_admin_hiding_spot_audits_actor_and_marks_it_closed_in_search(): void
    {
        [$spot, $prefecture] = $this->parkingSpot();
        $admin = User::factory()->create(['prefecture_id' => $prefecture->id, 'is_admin' => true]);
        $history = ParkingSpotUpdateHistory::create(['parking_spot_id' => $spot->id, 'user_id' => $admin->id, 'changes' => []]);
        ParkingSpotReport::create(['parking_spot_id' => $spot->id, 'parking_spot_update_history_id' => $history->id, 'user_id' => $admin->id, 'reason' => '確認が必要です。']);

        $this->actingAs($admin)->post(route('admin.parking_spots.hide', $spot), ['moderation_reason' => '閉鎖を確認しました。'])->assertRedirect();

        $this->assertDatabaseHas('parking_spots', ['id' => $spot->id, 'is_published' => false]);
        $this->assertDatabaseHas('parking_spot_moderation_actions', ['parking_spot_id' => $spot->id, 'user_id' => $admin->id, 'action' => 'hidden', 'details->reason' => '閉鎖を確認しました。']);
        $this->get(route('parking_spot.show', $spot))
            ->assertOk()
            ->assertSee('この駐輪場は閉鎖済みです。')
            ->assertDontSee('>編集<', false)
            ->assertDontSee(route('reviews.store', $spot), false)
            ->assertSee('閉鎖済みの駐輪場には評価を投稿できません。');
        $this->actingAs($admin)->get(route('parking_spot.edit', $spot))->assertForbidden();
        $this->actingAs($admin)->post(route('reviews.store', $spot), ['rating' => 5, 'comment' => '投稿できないはずです。'])->assertForbidden();
        $this->get(route('reviews.index', $spot))->assertOk();
        $this->get(route('home'))->assertDontSee($spot->name);
        Livewire::test(ParkingSpots::class)
            ->call('updateBounds', ['south' => 35.0, 'north' => 36.0, 'west' => 139.0, 'east' => 140.0])
            ->assertSee($spot->name)
            ->assertSee('閉鎖済み')
            ->set('excludeClosedDraft', true)
            ->call('applyFilters')
            ->assertDontSee($spot->name);
        $this->actingAs($admin)->get(route('admin.parking_spot_reports.index'))
            ->assertOk()
            ->assertSee('公開する')
            ->assertSee('この更新時点へ差し戻す');
    }

    public function test_admin_can_publish_a_hidden_spot_and_the_action_is_audited(): void
    {
        [$spot, $prefecture] = $this->parkingSpot();
        $spot->is_published = false;
        $spot->save();
        $admin = User::factory()->create(['prefecture_id' => $prefecture->id, 'is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.parking_spots.publish', $spot), ['moderation_reason' => '営業再開を確認しました。'])->assertRedirect();

        $this->assertDatabaseHas('parking_spots', ['id' => $spot->id, 'is_published' => true]);
        $this->assertDatabaseHas('parking_spot_moderation_actions', [
            'parking_spot_id' => $spot->id,
            'user_id' => $admin->id,
            'action' => 'published',
            'details->reason' => '営業再開を確認しました。',
        ]);
        $this->get(route('parking_spot.show', $spot))->assertOk();
    }

    public function test_admin_can_restore_basic_information_and_rates_to_a_selected_history_without_changing_images(): void
    {
        [$spot, $prefecture] = $this->parkingSpot();
        $spot->forceFill(['name' => '最新の名称', 'capacity' => 9, 'image_path' => 'parking-spots/current.jpg'])->save();
        ParkingSpotRates::create(['parking_spot_id' => $spot->id, 'day_type' => '平日', 'start_time' => '09:00', 'end_time' => '18:00', 'unit_minutes' => 60, 'rate' => 300, 'free_minutes' => 0, 'max_rate' => 1800]);
        $target = ParkingSpotUpdateHistory::create(['parking_spot_id' => $spot->id, 'user_id' => $spot->user_id, 'changes' => ['name' => ['before' => '元の名称', 'after' => '中間の名称'], 'capacity' => ['before' => 1, 'after' => 2], 'rates' => ['before' => [['day_type' => '全日', 'start_time' => '00:00', 'end_time' => '00:00', 'unit_minutes' => 30, 'rate' => 100, 'free_minutes' => 0, 'max_rate' => 1000]], 'after' => []]]]);
        $later = ParkingSpotUpdateHistory::create(['parking_spot_id' => $spot->id, 'user_id' => $spot->user_id, 'changes' => ['name' => ['before' => '中間の名称', 'after' => '最新の名称'], 'capacity' => ['before' => 2, 'after' => 9], 'rates' => ['before' => [['day_type' => '全日', 'start_time' => '00:00', 'end_time' => '00:00', 'unit_minutes' => 30, 'rate' => 200, 'free_minutes' => 0, 'max_rate' => 1200]], 'after' => []], 'images' => ['before' => ['old.jpg'], 'after' => ['parking-spots/current.jpg']]]]);
        $admin = User::factory()->create(['prefecture_id' => $prefecture->id, 'is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.parking_spots.histories.restore', [$spot, $target]), ['moderation_reason' => '誤登録を修正します。'])->assertRedirect();

        $spot->refresh();
        $this->assertSame('中間の名称', $spot->name);
        $this->assertSame(2, $spot->capacity);
        $this->assertSame('parking-spots/current.jpg', $spot->image_path);
        $this->assertDatabaseHas('parking_spot_rates', ['parking_spot_id' => $spot->id, 'rate' => 200, 'max_rate' => 1200]);
        $this->assertDatabaseHas('parking_spot_moderation_actions', ['parking_spot_id' => $spot->id, 'parking_spot_update_history_id' => $target->id, 'user_id' => $admin->id, 'action' => 'restored', 'details->reason' => '誤登録を修正します。']);
    }

    public function test_editor_can_mark_a_closed_spot_as_unpublished(): void
    {
        [$spot, $prefecture] = $this->parkingSpot();
        $editor = User::factory()->create(['prefecture_id' => $prefecture->id]);

        $this->actingAs($editor)->get(route('parking_spot.edit', $spot))
            ->assertOk()
            ->assertSee('閉鎖済み')
            ->assertSee('誤登録')
            ->assertSee('x-data=""', false)
            ->assertSee('閉鎖済みとして掲載停止しますか？')
            ->assertSee('誤登録として削除を申請しますか？');
        $this->actingAs($editor)->post(route('parking_spot.close', $spot))->assertRedirect(route('home'));

        $this->assertDatabaseHas('parking_spots', ['id' => $spot->id, 'is_published' => false]);
        $this->assertDatabaseHas('parking_spot_moderation_actions', [
            'parking_spot_id' => $spot->id,
            'user_id' => $editor->id,
            'action' => 'hidden',
            'details->reason' => '編集画面から閉鎖済みとして掲載停止',
        ]);
    }

    public function test_incorrect_registration_requires_admin_confirmation_before_physical_deletion(): void
    {
        [$spot, $prefecture] = $this->parkingSpot();
        $requester = User::factory()->create(['prefecture_id' => $prefecture->id]);
        $admin = User::factory()->create(['prefecture_id' => $prefecture->id, 'is_admin' => true]);
        Storage::fake('public');
        Storage::disk('public')->put('parking-spots/deleted.webp', 'image');
        $spot->forceFill(['image_path' => 'parking-spots/deleted.webp'])->save();
        ParkingSpotImage::forceCreate(['parking_spot_id' => $spot->id, 'user_id' => $requester->id, 'path' => 'parking-spots/deleted.webp', 'position' => 0]);
        ParkingSpotRates::create(['parking_spot_id' => $spot->id, 'day_type' => '全日', 'start_time' => '00:00', 'end_time' => '00:00', 'unit_minutes' => 60, 'rate' => 100, 'free_minutes' => 0, 'max_rate' => 1000]);
        Favorite::forceCreate(['parking_spot_id' => $spot->id, 'user_id' => $requester->id]);
        Review::forceCreate(['parking_spot_id' => $spot->id, 'user_id' => $requester->id, 'rating' => 5, 'comment' => '削除対象です。']);
        ParkingSpotUpdateHistory::create(['parking_spot_id' => $spot->id, 'user_id' => $requester->id, 'changes' => []]);
        ParkingSpotReport::create(['parking_spot_id' => $spot->id, 'user_id' => $requester->id, 'reason' => '関連通報です。']);

        $this->actingAs($requester)->post(route('parking_spot.deletion_requests.store', $spot), ['reason' => '存在しない施設です。'])
            ->assertRedirect(route('parking_spot.edit', $spot));
        $deletionRequest = ParkingSpotDeletionRequest::sole();
        $this->assertDatabaseHas('parking_spots', ['id' => $spot->id]);

        $this->actingAs($admin)->post(route('admin.parking_spot_deletion_requests.delete', $deletionRequest), ['moderation_reason' => '現地確認で誤登録と判断しました。'])
            ->assertRedirect();

        $this->assertDatabaseMissing('parking_spots', ['id' => $spot->id]);
        $this->assertDatabaseMissing('parking_spot_rates', ['parking_spot_id' => $spot->id]);
        $this->assertDatabaseMissing('parking_spot_images', ['parking_spot_id' => $spot->id]);
        $this->assertDatabaseMissing('favorites', ['parking_spot_id' => $spot->id]);
        $this->assertDatabaseMissing('reviews', ['parking_spot_id' => $spot->id]);
        $this->assertDatabaseMissing('parking_spot_update_histories', ['parking_spot_id' => $spot->id]);
        $this->assertDatabaseMissing('parking_spot_reports', ['parking_spot_id' => $spot->id]);
        $this->assertDatabaseHas('parking_spot_deletion_requests', [
            'id' => $deletionRequest->id,
            'parking_spot_id' => null,
            'status' => 'deleted',
            'reviewed_by' => $admin->id,
            'resolution_reason' => '現地確認で誤登録と判断しました。',
        ]);
        Storage::disk('public')->assertMissing('parking-spots/deleted.webp');
    }

    public function test_admin_moderation_requires_a_reason(): void
    {
        [$spot, $prefecture] = $this->parkingSpot();
        $admin = User::factory()->create(['prefecture_id' => $prefecture->id, 'is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.parking_spots.hide', $spot))
            ->assertSessionHasErrors('moderation_reason');
    }

    private function parkingSpot(?Prefecture $prefecture = null): array
    {
        $prefecture ??= Prefecture::create(['name' => '東京都', 'name_kana' => 'トウキョウト']);
        $city = City::firstOrCreate(['prefecture_id' => $prefecture->id, 'name' => '千代田区'], ['name_kana' => 'チヨダク']);
        $postalcode = Postalcode::firstOrCreate(['postalcode' => '1000001'], ['city_id' => $city->id, 'name' => '千代田', 'name_kana' => 'チヨダ']);
        $owner = User::factory()->create(['prefecture_id' => $prefecture->id]);
        $spot = ParkingSpot::forceCreate(['user_id' => $owner->id, 'name' => '通報対象駐輪場', 'postalcode_id' => $postalcode->id, 'address' => '東京都千代田区千代田1-1', 'longitude' => 139.753, 'latitude' => 35.685, 'capacity' => 1, 'opening_time' => '00:00', 'closing_time' => '00:00']);

        return [$spot, $prefecture];
    }
}
