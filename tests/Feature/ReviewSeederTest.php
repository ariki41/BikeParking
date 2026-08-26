<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\ReviewSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_valid_unique_reviews_for_existing_parking_spots(): void
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
        $users = User::factory(3)->create(['prefecture_id' => $prefecture->id]);

        foreach (range(1, 2) as $number) {
            ParkingSpot::forceCreate([
                'user_id' => $users->first()->id,
                'name' => "シーダーテスト駐輪場{$number}",
                'postalcode_id' => $postalcode->id,
                'address' => "東京都千代田区千代田1-{$number}",
                'longitude' => 139.753000,
                'latitude' => 35.685000,
                'capacity' => 1,
                'opening_time' => '00:00:00',
                'closing_time' => '00:00:00',
            ]);
        }

        $expectedReviewCount = ParkingSpot::query()->pluck('id')->sum(
            fn (int $parkingSpotId): int => min(1 + ($parkingSpotId % 5), $users->count())
        );

        $this->seed(ReviewSeeder::class);

        $this->assertDatabaseCount('reviews', $expectedReviewCount);
        $this->assertTrue(Review::all()->every(
            fn (Review $review): bool => $review->rating >= 1
                && $review->rating <= 5
                && filled($review->comment)
        ));
        $this->assertTrue(Review::all()
            ->groupBy(fn (Review $review): string => "{$review->user_id}-{$review->parking_spot_id}")
            ->every(fn ($reviews): bool => $reviews->count() === 1));

        $this->seed(ReviewSeeder::class);

        $this->assertDatabaseCount('reviews', $expectedReviewCount);
    }
}
