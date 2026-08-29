<?php

namespace Database\Seeders;

use App\Models\ParkingSpot;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewSeeder extends Seeder
{
    private const BASE_REVIEW_COUNT = 12;

    private const REVIEW_COUNT_VARIATION = 4;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Review::query()->delete();

        $userIds = User::query()->orderBy('id')->pluck('id')->values();

        if ($userIds->isEmpty() || ! ParkingSpot::query()->exists()) {
            return;
        }

        $comments = [
            1 => '駐輪場までの案内が分かりにくく、混雑していました。',
            2 => '場所は便利ですが、設備や使いやすさに改善の余地があります。',
            3 => '料金と立地のバランスがよく、普段使いには十分です。',
            4 => '駅から近く、屋根もあって使いやすい駐輪場です。',
            5 => 'アクセス、料金、設備のすべてに満足しています。',
        ];
        $seededAt = now();

        ParkingSpot::query()
            ->select('id')
            ->chunkById(100, function ($parkingSpots) use ($comments, $seededAt, $userIds): void {
                $reviews = [];
                $userCount = $userIds->count();

                foreach ($parkingSpots as $parkingSpot) {
                    $reviewCount = min(
                        self::BASE_REVIEW_COUNT + ($parkingSpot->id % self::REVIEW_COUNT_VARIATION),
                        $userCount,
                    );
                    $firstUserIndex = $parkingSpot->id % $userCount;

                    for ($offset = 0; $offset < $reviewCount; $offset++) {
                        $rating = 1 + (($parkingSpot->id + ($offset * 3)) % 5);
                        $reviewedAt = $seededAt->copy()->subDays(($parkingSpot->id + $offset) % 30);

                        $reviews[] = [
                            'user_id' => $userIds[($firstUserIndex + $offset) % $userCount],
                            'parking_spot_id' => $parkingSpot->id,
                            'rating' => $rating,
                            'comment' => $comments[$rating],
                            'created_at' => $reviewedAt,
                            'updated_at' => $reviewedAt,
                        ];
                    }
                }

                DB::table('reviews')->insert($reviews);
            });
    }
}
