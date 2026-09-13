<?php

namespace Tests\Feature;

use App\Livewire\ParkingSpots;
use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\ParkingSpotRates;
use App\Models\ParkingSpotReport;
use App\Models\ParkingSpotUpdateHistory;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->actingAs($user)->post(route('admin.parking_spots.hide', $spot))->assertForbidden();
        $this->actingAs($user)->post(route('admin.parking_spots.histories.restore', [$spot, $history]))->assertForbidden();
    }

    public function test_admin_hiding_spot_audits_actor_and_excludes_public_routes(): void
    {
        [$spot, $prefecture] = $this->parkingSpot();
        $admin = User::factory()->create(['prefecture_id' => $prefecture->id, 'is_admin' => true]);
        $history = ParkingSpotUpdateHistory::create(['parking_spot_id' => $spot->id, 'user_id' => $admin->id, 'changes' => []]);
        ParkingSpotReport::create(['parking_spot_id' => $spot->id, 'parking_spot_update_history_id' => $history->id, 'user_id' => $admin->id, 'reason' => '確認が必要です。']);

        $this->actingAs($admin)->post(route('admin.parking_spots.hide', $spot))->assertRedirect();

        $this->assertDatabaseHas('parking_spots', ['id' => $spot->id, 'is_published' => false]);
        $this->assertDatabaseHas('parking_spot_moderation_actions', ['parking_spot_id' => $spot->id, 'user_id' => $admin->id, 'action' => 'hidden']);
        $this->get(route('parking_spot.show', $spot))->assertNotFound();
        $this->get(route('reviews.index', $spot))->assertNotFound();
        $this->get(route('home'))->assertDontSee($spot->name);
        Livewire::test(ParkingSpots::class)
            ->call('updateBounds', ['south' => 35.0, 'north' => 36.0, 'west' => 139.0, 'east' => 140.0])
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

        $this->actingAs($admin)->post(route('admin.parking_spots.publish', $spot))->assertRedirect();

        $this->assertDatabaseHas('parking_spots', ['id' => $spot->id, 'is_published' => true]);
        $this->assertDatabaseHas('parking_spot_moderation_actions', [
            'parking_spot_id' => $spot->id,
            'user_id' => $admin->id,
            'action' => 'published',
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

        $this->actingAs($admin)->post(route('admin.parking_spots.histories.restore', [$spot, $target]))->assertRedirect();

        $spot->refresh();
        $this->assertSame('中間の名称', $spot->name);
        $this->assertSame(2, $spot->capacity);
        $this->assertSame('parking-spots/current.jpg', $spot->image_path);
        $this->assertDatabaseHas('parking_spot_rates', ['parking_spot_id' => $spot->id, 'rate' => 200, 'max_rate' => 1200]);
        $this->assertDatabaseHas('parking_spot_moderation_actions', ['parking_spot_id' => $spot->id, 'parking_spot_update_history_id' => $target->id, 'user_id' => $admin->id, 'action' => 'restored']);
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
