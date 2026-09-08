<?php

namespace Tests\Feature\Concerns;

use App\Domain\ParkingSpots\EngineDisplacementClass;
use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use App\Services\ParkingSpotConfirmationService;
use Illuminate\Support\Facades\Hash;

trait InteractsWithParkingSpotRateFixtures
{
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

        $user = User::create([
            'user_id' => 'rate-user',
            'name' => 'Rate User',
            'password' => Hash::make('password'),
            'prefecture_id' => $prefecture->id,
        ]);

        $parkingSpot = ParkingSpot::forceCreate([
            'user_id' => $user->id,
            'name' => '料金表示テスト駐輪場',
            'postalcode_id' => $postalcode->id,
            'address' => '東京都千代田区千代田1-1',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'max_displacement_class' => EngineDisplacementClass::UpTo400cc->value,
            'opening_time' => '00:00:00',
            'closing_time' => '00:00:00',
        ]);

        return [$parkingSpot, $user, $postalcode];
    }

    private function validParkingSpotInput(Postalcode $postalcode, array $overrides = []): array
    {
        $input = [
            'name' => '料金バリデーションテスト駐輪場',
            'postalcode' => $postalcode->postalcode,
            'address1' => '東京都千代田区千代田',
            'address2' => '1-2',
            'capacity' => 1,
            'max_displacement_class' => EngineDisplacementClass::UpTo400cc->value,
            'opening_time' => '00:00',
            'closing_time' => '00:00',
            'rates' => [$this->validRateInput()],
        ];

        return array_replace($input, $overrides);
    }

    private function validRateInput(array $overrides = []): array
    {
        return array_replace([
            'day_type' => '全日',
            'start_time' => '00:00',
            'end_time' => '00:00',
            'unit_minutes' => 30,
            'rate' => 100,
            'free_minutes' => 0,
            'max_rate' => 1200,
        ], $overrides);
    }

    private function confirmationState(string $mode, array $input): array
    {
        return [
            ParkingSpotConfirmationService::SESSION_KEY => [
                'mode' => $mode,
                'parking_spot_id' => $input['id'] ?? null,
                'input' => $input,
                'temporary_image_paths' => array_values(array_filter(
                    $input['image_paths'] ?? array_values(array_filter([$input['image_path'] ?? null])),
                    fn ($path) => is_string($path) && str_starts_with($path, 'temp/parking-spots/'),
                )),
                'expires_at' => now()->addDay()->getTimestamp(),
            ],
        ];
    }
}
