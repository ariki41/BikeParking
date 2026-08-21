<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\ParkingSpotRates;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use LogicException;

class DevelopmentSeeder extends Seeder
{
    /**
     * Seed development-only sample data without removing existing records.
     */
    public function run(): void
    {
        $password = config('development.seed_password');

        if (! is_string($password) || $password === '') {
            throw new LogicException('DEVELOPMENT_SEED_PASSWORD must be configured before seeding development data.');
        }

        $prefecture = Prefecture::query()->firstOrCreate(
            ['name' => '東京都'],
            ['name_kana' => 'トウキョウト'],
        );
        $city = City::query()->firstOrCreate(
            ['prefecture_id' => $prefecture->id, 'name' => '千代田区'],
            ['name_kana' => 'チヨダク'],
        );
        $postalcode = Postalcode::query()->firstOrCreate(
            ['postalcode' => '1000001', 'city_id' => $city->id],
            ['name' => '千代田', 'name_kana' => 'チヨダ'],
        );

        $owner = $this->upsertUser($prefecture, $password, 'development-owner', '開発用オーナー');
        $reviewer = $this->upsertUser($prefecture, $password, 'development-reviewer', '開発用レビュアー');

        $parkingSpots = collect([
            [
                'name' => '開発用 東京駅前駐輪場',
                'address' => '東京都千代田区千代田1-1',
                'longitude' => 139.767125,
                'latitude' => 35.681236,
                'capacity' => 1,
                'opening_time' => '00:00:00',
                'closing_time' => '00:00:00',
                'rates' => [
                    ['day_type' => '全日', 'start_time' => '00:00:00', 'end_time' => '00:00:00', 'unit_minutes' => 60, 'rate' => 100, 'free_minutes' => 0, 'max_rate' => 1000],
                ],
            ],
            [
                'name' => '開発用 神田駅東口駐輪場',
                'address' => '東京都千代田区鍛冶町2-13-1',
                'longitude' => 139.770700,
                'latitude' => 35.691500,
                'capacity' => 2,
                'opening_time' => '06:00:00',
                'closing_time' => '23:00:00',
                'rates' => [
                    ['day_type' => '平日', 'start_time' => '08:00:00', 'end_time' => '20:00:00', 'unit_minutes' => 30, 'rate' => 100, 'free_minutes' => 0, 'max_rate' => 1200],
                    ['day_type' => '土日祝', 'start_time' => '08:00:00', 'end_time' => '20:00:00', 'unit_minutes' => 30, 'rate' => 150, 'free_minutes' => 0, 'max_rate' => 1800],
                ],
            ],
            [
                'name' => '開発用 霞が関駐輪場',
                'address' => '東京都千代田区霞が関1-2-1',
                'longitude' => 139.752800,
                'latitude' => 35.673900,
                'capacity' => 3,
                'opening_time' => '00:00:00',
                'closing_time' => '00:00:00',
                'rates' => [
                    ['day_type' => '平日', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'unit_minutes' => 30, 'rate' => 100, 'free_minutes' => 30, 'max_rate' => null],
                    ['day_type' => '夜間', 'start_time' => '18:00:00', 'end_time' => '09:00:00', 'unit_minutes' => 60, 'rate' => 100, 'free_minutes' => 0, 'max_rate' => 700],
                ],
            ],
        ])->mapWithKeys(function (array $attributes) use ($owner, $postalcode): array {
            $rates = $attributes['rates'];
            unset($attributes['rates']);

            $parkingSpot = ParkingSpot::query()->firstOrNew([
                'user_id' => $owner->id,
                'name' => $attributes['name'],
            ]);
            $parkingSpot->forceFill([
                ...$attributes,
                'user_id' => $owner->id,
                'postalcode' => $postalcode->id,
            ])->save();

            foreach ($rates as $rate) {
                ParkingSpotRates::query()->updateOrCreate(
                    [
                        'parking_spot_id' => $parkingSpot->id,
                        'day_type' => $rate['day_type'],
                        'start_time' => $rate['start_time'],
                        'end_time' => $rate['end_time'],
                    ],
                    $rate,
                );
            }

            return [$attributes['name'] => $parkingSpot];
        });

        $this->upsertReview(
            $reviewer,
            $parkingSpots['開発用 東京駅前駐輪場'],
            5,
            '駅から近く、開発時の表示確認に使いやすい駐輪場です。',
        );
        $this->upsertReview(
            $reviewer,
            $parkingSpots['開発用 神田駅東口駐輪場'],
            3,
            '平日と休日の料金表示を確認できます。',
        );
        $this->upsertReview(
            $owner,
            $parkingSpots['開発用 霞が関駐輪場'],
            4,
            '無料時間、最大料金なし、日付またぎの料金を含むテストデータです。',
        );

        $this->command?->info('Development sample data is ready.');
    }

    private function upsertUser(Prefecture $prefecture, string $password, string $userId, string $name): User
    {
        $user = User::query()->firstOrNew(['user_id' => $userId]);
        $user->forceFill([
            'name' => $name,
            'prefecture_id' => $prefecture->id,
        ]);

        if (! $user->exists || ! Hash::check($password, $user->password)) {
            $user->password = $password;
        }

        $user->save();

        return $user;
    }

    private function upsertReview(User $user, ParkingSpot $parkingSpot, int $rating, string $comment): void
    {
        $review = Review::query()->firstOrNew([
            'user_id' => $user->id,
            'parking_spot_id' => $parkingSpot->id,
        ]);
        $review->forceFill([
            'rating' => $rating,
            'comment' => $comment,
        ])->save();
    }
}
