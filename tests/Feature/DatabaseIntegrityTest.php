<?php

namespace Tests\Feature;

use App\Domain\ParkingSpots\EngineDisplacementClass;
use App\Models\Favorite;
use App\Models\ParkingSpot;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_integrity_suite_runs_on_mysql(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
    }

    public function test_parking_spot_factory_creates_all_required_relations_on_an_empty_database(): void
    {
        $parkingSpot = ParkingSpot::factory()->create();

        $this->assertNotNull($parkingSpot->user()->first());
        $this->assertNotNull($parkingSpot->postalcode()->first());
        $this->assertNotNull($parkingSpot->postalcode->city()->first());
        $this->assertNotNull($parkingSpot->postalcode->city->prefecture()->first());
        $this->assertTrue($parkingSpot->postalcode->is_active);
        $this->assertContains($parkingSpot->capacity, array_keys(config('categories.parking_spot_capacity')));
        $this->assertContains($parkingSpot->max_displacement_class, EngineDisplacementClass::cases());
    }

    public function test_deleting_a_parking_spot_owner_preserves_anonymized_parking_spot_content(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $parkingSpot = ParkingSpot::factory()->for($owner)->create();

        $parkingSpot->rates()->create($this->rateAttributes());
        $image = $parkingSpot->images()->create([
            'user_id' => $owner->id,
            'path' => 'parking-spots/test.webp',
            'position' => 0,
        ]);
        $parkingSpot->updateHistories()->create(['user_id' => $otherUser->id, 'changes' => []]);
        $otherUser->favorites()->create(['parking_spot_id' => $parkingSpot->id]);
        $review = $otherUser->reviews()->make(['rating' => 5, 'comment' => '使いやすいです。']);
        $review->parkingSpot()->associate($parkingSpot);
        $review->save();
        $tagId = DB::table('tags')->insertGetId([
            'name' => '屋根あり',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('parking_spot_tags')->insert([
            'parking_spot_id' => $parkingSpot->id,
            'tag_id' => $tagId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $owner->delete();

        $this->assertDatabaseHas('parking_spots', [
            'id' => $parkingSpot->id,
            'user_id' => null,
        ]);
        $this->assertDatabaseHas('parking_spot_rates', ['parking_spot_id' => $parkingSpot->id]);
        $this->assertDatabaseHas('parking_spot_images', [
            'id' => $image->id,
            'parking_spot_id' => $parkingSpot->id,
            'user_id' => null,
        ]);
        $this->assertDatabaseHas('parking_spot_update_histories', [
            'parking_spot_id' => $parkingSpot->id,
            'user_id' => $otherUser->id,
        ]);
        $this->assertDatabaseHas('favorites', ['parking_spot_id' => $parkingSpot->id]);
        $this->assertDatabaseHas('reviews', ['parking_spot_id' => $parkingSpot->id]);
        $this->assertDatabaseHas('parking_spot_tags', ['parking_spot_id' => $parkingSpot->id]);
        $this->assertDatabaseHas('tags', ['id' => $tagId]);
        $this->assertDatabaseHas('users', ['id' => $otherUser->id]);
    }

    public function test_deleting_a_user_removes_favorites_and_anonymizes_retained_rows(): void
    {
        $owner = User::factory()->create();
        $departingUser = User::factory()->create();
        $parkingSpot = ParkingSpot::factory()->for($owner)->create();

        $departingUser->favorites()->create(['parking_spot_id' => $parkingSpot->id]);
        $review = $departingUser->reviews()->make(['rating' => 4, 'comment' => '便利でした。']);
        $review->parkingSpot()->associate($parkingSpot);
        $review->save();
        $history = $parkingSpot->updateHistories()->create([
            'user_id' => $departingUser->id,
            'changes' => ['name' => ['before' => '変更前', 'after' => '変更後']],
        ]);
        DB::table('sessions')->insert([
            'id' => 'departing-user-session',
            'user_id' => $departingUser->id,
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $departingUser->delete();

        $this->assertDatabaseHas('parking_spots', ['id' => $parkingSpot->id]);
        $this->assertDatabaseMissing('favorites', ['user_id' => $departingUser->id]);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => null,
        ]);
        $this->assertDatabaseHas('parking_spot_update_histories', [
            'id' => $history->id,
            'user_id' => null,
        ]);
        $this->assertDatabaseHas('sessions', [
            'id' => 'departing-user-session',
            'user_id' => null,
        ]);
    }

    public function test_duplicate_parking_spot_tag_relations_are_rejected(): void
    {
        $parkingSpot = ParkingSpot::factory()->create();
        $tagId = DB::table('tags')->insertGetId([
            'name' => '駅近',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $relation = [
            'parking_spot_id' => $parkingSpot->id,
            'tag_id' => $tagId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table('parking_spot_tags')->insert($relation);

        $this->expectException(QueryException::class);

        DB::table('parking_spot_tags')->insert($relation);
    }

    public function test_duplicate_parking_spot_rate_schedules_are_rejected(): void
    {
        $parkingSpot = ParkingSpot::factory()->create();
        $parkingSpot->rates()->create($this->rateAttributes());

        $this->expectException(QueryException::class);

        $parkingSpot->rates()->create([
            ...$this->rateAttributes(),
            'rate' => 200,
        ]);
    }

    public function test_duplicate_favorites_and_reviews_are_rejected(): void
    {
        $user = User::factory()->create();
        $parkingSpot = ParkingSpot::factory()->create();

        Favorite::forceCreate([
            'user_id' => $user->id,
            'parking_spot_id' => $parkingSpot->id,
        ]);
        Review::forceCreate([
            'user_id' => $user->id,
            'parking_spot_id' => $parkingSpot->id,
            'rating' => 4,
            'comment' => '最初のレビューです。',
        ]);

        try {
            Favorite::forceCreate([
                'user_id' => $user->id,
                'parking_spot_id' => $parkingSpot->id,
            ]);
            $this->fail('Duplicate favorites must be rejected.');
        } catch (QueryException) {
            // Expected: the user and parking spot pair is unique.
        }

        $this->expectException(QueryException::class);

        Review::forceCreate([
            'user_id' => $user->id,
            'parking_spot_id' => $parkingSpot->id,
            'rating' => 5,
            'comment' => '重複したレビューです。',
        ]);
    }

    public function test_review_rating_outside_one_to_five_is_rejected_by_the_database(): void
    {
        $user = User::factory()->create();
        $parkingSpot = ParkingSpot::factory()->create();

        $this->expectException(QueryException::class);

        Review::forceCreate([
            'user_id' => $user->id,
            'parking_spot_id' => $parkingSpot->id,
            'rating' => 6,
            'comment' => '範囲外の評価です。',
        ]);
    }

    public function test_referenced_address_master_records_cannot_be_deleted(): void
    {
        $parkingSpot = ParkingSpot::factory()->create();

        $this->expectException(QueryException::class);

        $parkingSpot->postalcode->delete();
    }

    public function test_map_bounds_have_a_composite_location_index(): void
    {
        $locationIndex = collect(Schema::getIndexes('parking_spots'))
            ->firstWhere('name', 'parking_spots_location_index');

        $this->assertNotNull($locationIndex);
        $this->assertSame(['latitude', 'longitude'], $locationIndex['columns']);
    }

    /**
     * @return array<string, int|string|null>
     */
    private function rateAttributes(): array
    {
        return [
            'day_type' => '全日',
            'start_time' => '00:00:00',
            'end_time' => '00:00:00',
            'unit_minutes' => 30,
            'rate' => 100,
            'free_minutes' => 0,
            'max_rate' => null,
        ];
    }
}
