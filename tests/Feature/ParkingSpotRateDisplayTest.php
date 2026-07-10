<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\ParkingSpotRates;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use App\Services\ParkingSpotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ParkingSpotRateDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_parking_spot_detail_displays_rates(): void
    {
        [$parkingSpot, $user] = $this->createParkingSpot();

        ParkingSpotRates::create([
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '平日',
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'unit_minutes' => 30,
            'rate' => 100,
            'free_minutes' => 30,
            'max_rate' => 1200,
        ]);

        $response = $this->actingAs($user)
            ->get(route('parking_spot.show', $parkingSpot->id));

        $response->assertOk();
        $response->assertSee('区分');
        $response->assertSee('時間帯');
        $response->assertSee('料金');
        $response->assertSee('最大料金');
        $response->assertSee('平日');
        $response->assertSee('08:00');
        $response->assertSee('20:00');
        $response->assertSee('最初の30分無料 / 以降30分 100円');
        $response->assertSee('1,200円');
    }

    public function test_parking_spot_detail_displays_no_max_rate_label(): void
    {
        [$parkingSpot, $user] = $this->createParkingSpot();

        ParkingSpotRates::create([
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '全日',
            'start_time' => '00:00:00',
            'end_time' => '00:00:00',
            'unit_minutes' => 30,
            'rate' => 100,
            'free_minutes' => 0,
            'max_rate' => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('parking_spot.show', $parkingSpot->id));

        $response->assertOk();
        $response->assertSee('30分 100円');
        $response->assertSee('最大料金なし');
        $response->assertDontSee('最初の0分無料');
        $response->assertDontSee('以降30分 100円');
    }

    public function test_parking_spot_detail_displays_overnight_rate_end_time_as_next_day(): void
    {
        [$parkingSpot, $user] = $this->createParkingSpot();

        ParkingSpotRates::create([
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '夜間',
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
            'unit_minutes' => 60,
            'rate' => 200,
            'free_minutes' => 0,
            'max_rate' => 800,
        ]);

        $response = $this->actingAs($user)
            ->get(route('parking_spot.show', $parkingSpot->id));

        $response->assertOk();
        $response->assertSee('22:00 ～ 翌06:00');
    }

    public function test_parking_spot_detail_displays_placeholder_without_rates(): void
    {
        [$parkingSpot, $user] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->get(route('parking_spot.show', $parkingSpot->id));

        $response->assertOk();
        $response->assertSee('料金未登録');
    }

    public function test_parking_spot_can_save_multiple_rates(): void
    {
        [$parkingSpot, $user, $postalcode] = $this->createParkingSpot();

        $this->actingAs($user);

        app(ParkingSpotService::class)->saveParkingSpot([
            'name' => '複数料金テスト駐車場',
            'postalcode' => $postalcode->postalcode,
            'address' => '東京都千代田区千代田1-2',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'opening_time' => '00:00',
            'closing_time' => '00:00',
            'rates' => [
                [
                    'day_type' => '平日',
                    'start_time' => '08:00',
                    'end_time' => '20:00',
                    'unit_minutes' => 30,
                    'rate' => 100,
                    'free_minutes' => 0,
                    'max_rate' => 1200,
                ],
                [
                    'day_type' => '土日祝',
                    'start_time' => '08:00',
                    'end_time' => '20:00',
                    'unit_minutes' => 60,
                    'rate' => 300,
                    'free_minutes' => 30,
                    'max_rate' => 1800,
                ],
            ],
        ]);

        $this->assertDatabaseHas('parking_spot_rates', [
            'day_type' => '平日',
            'unit_minutes' => 30,
            'rate' => 100,
        ]);
        $this->assertDatabaseHas('parking_spot_rates', [
            'day_type' => '土日祝',
            'unit_minutes' => 60,
            'rate' => 300,
            'free_minutes' => 30,
        ]);
        $this->assertDatabaseCount('parking_spot_rates', 2);
    }

    public function test_parking_spot_store_creates_rates_from_confirmed_form(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $input = [
            'name' => '登録Featureテスト駐車場',
            'postalcode' => $postalcode->postalcode,
            'address' => '東京都千代田区千代田1-2',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'opening_time' => '00:00',
            'closing_time' => '00:00',
            'rates' => [
                $this->validRateInput([
                    'day_type' => '平日',
                    'start_time' => '08:00',
                    'end_time' => '20:00',
                    'unit_minutes' => 30,
                    'rate' => 100,
                    'free_minutes' => 0,
                    'max_rate' => 1200,
                ]),
            ],
        ];

        $response = $this->actingAs($user)
            ->withSession(['create_parking_spot_form' => $input])
            ->post(route('parking_spot.store'));

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('parking_spots', [
            'name' => '登録Featureテスト駐車場',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('parking_spot_rates', [
            'day_type' => '平日',
            'start_time' => '08:00',
            'end_time' => '20:00',
            'unit_minutes' => 30,
            'rate' => 100,
            'free_minutes' => 0,
            'max_rate' => 1200,
        ]);
    }

    public function test_parking_spot_create_form_displays_rate_unit_select(): void
    {
        [, $user] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->get(route('parking_spot.create'));

        $response->assertOk();
        $response->assertSee('name="rates[0][unit_minutes]"', false);
        $response->assertSee('<option value="12"', false);
        $response->assertSee('<option value="15"', false);
        $response->assertSee('<option value="30" selected', false);
        $response->assertSee('<option value="60"', false);
        $response->assertSee('<option value="120"', false);
        $response->assertSeeText('2時間');
        $response->assertSee('<option value="180"', false);
        $response->assertSeeText('3時間');
        $response->assertSee('<option value="240"', false);
        $response->assertSeeText('4時間');
        $response->assertSee('<option value="300"', false);
        $response->assertSeeText('5時間');
        $response->assertSee('<option value="720"', false);
        $response->assertSeeText('12時間');
        $response->assertSee('<option value="1440"', false);
        $response->assertSeeText('24時間');
    }

    public function test_parking_spot_create_form_can_select_no_free_minutes(): void
    {
        [, $user] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->get(route('parking_spot.create'));

        $response->assertOk();
        $response->assertSeeText('無料時間なし');
        $response->assertSee('name="rates[0][no_free_minutes]"', false);
        $response->assertSee('data-rate-field="no_free_minutes"', false);
    }

    public function test_no_free_minutes_input_is_normalized_on_confirm(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();
        Http::fake([
            '*' => Http::response([
                'Feature' => [
                    [
                        'Geometry' => ['Coordinates' => '139.753000,35.685000'],
                        'Property' => ['Address' => '東京都千代田区千代田1-2'],
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($user)
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'rates' => [
                    $this->validRateInput([
                        'free_minutes' => 30,
                        'no_free_minutes' => '1',
                    ]),
                ],
            ]));

        $response->assertOk();
        $response->assertSessionMissing('errors');
        $response->assertSessionHas('create_parking_spot_form.rates.0.free_minutes', 0);
        $response->assertDontSee('最初の30分無料');
        $response->assertDontSee('最初の0分無料');
        $response->assertSee('30分 100円');
    }

    public function test_overnight_rate_end_time_is_displayed_as_next_day_on_confirm(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();
        Http::fake([
            '*' => Http::response([
                'Feature' => [
                    [
                        'Geometry' => ['Coordinates' => '139.753000,35.685000'],
                        'Property' => ['Address' => '東京都千代田区千代田1-2'],
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($user)
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'rates' => [
                    $this->validRateInput([
                        'day_type' => '夜間',
                        'start_time' => '22:00',
                        'end_time' => '06:00',
                    ]),
                ],
            ]));

        $response->assertOk();
        $response->assertSessionMissing('errors');
        $response->assertSee('22:00 ～ 翌06:00');
    }

    public function test_parking_spot_can_save_rate_without_max_rate(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $this->actingAs($user);

        app(ParkingSpotService::class)->saveParkingSpot([
            'name' => '最大料金なしテスト駐車場',
            'postalcode' => $postalcode->postalcode,
            'address' => '東京都千代田区千代田1-2',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'opening_time' => '00:00',
            'closing_time' => '00:00',
            'rates' => [
                [
                    'day_type' => '全日',
                    'start_time' => '00:00',
                    'end_time' => '00:00',
                    'unit_minutes' => 30,
                    'rate' => 100,
                    'free_minutes' => 0,
                    'max_rate' => '',
                    'no_max_rate' => '1',
                ],
            ],
        ]);

        $this->assertDatabaseHas('parking_spot_rates', [
            'day_type' => '全日',
            'rate' => 100,
            'max_rate' => null,
        ]);
    }

    public function test_parking_spot_rate_validation_rejects_zero_yen_max_rate(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'rates' => [
                    $this->validRateInput([
                        'max_rate' => 0,
                    ]),
                ],
            ]));

        $response->assertRedirect(route('parking_spot.create'));
        $response->assertSessionHasErrors(['rates.0.max_rate']);
    }

    public function test_parking_spot_can_replace_rates_on_update(): void
    {
        [$parkingSpot] = $this->createParkingSpot();

        ParkingSpotRates::create([
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '平日',
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'unit_minutes' => 30,
            'rate' => 100,
            'free_minutes' => 0,
            'max_rate' => 1200,
        ]);

        app(ParkingSpotService::class)->updateParkingSpot([
            'id' => $parkingSpot->id,
            'name' => $parkingSpot->name,
            'postalcode' => '1000001',
            'address' => $parkingSpot->address,
            'longitude' => $parkingSpot->longitude,
            'latitude' => $parkingSpot->latitude,
            'capacity' => $parkingSpot->capacity,
            'opening_time' => '00:00',
            'closing_time' => '00:00',
            'rates' => [
                [
                    'day_type' => '夜間',
                    'start_time' => '20:00',
                    'end_time' => '08:00',
                    'unit_minutes' => 60,
                    'rate' => 200,
                    'free_minutes' => 0,
                    'max_rate' => 800,
                ],
                [
                    'day_type' => '全日',
                    'start_time' => '00:00',
                    'end_time' => '00:00',
                    'unit_minutes' => 15,
                    'rate' => 50,
                    'free_minutes' => 0,
                    'max_rate' => 500,
                ],
            ],
        ]);

        $this->assertDatabaseMissing('parking_spot_rates', [
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '平日',
        ]);
        $this->assertDatabaseHas('parking_spot_rates', [
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '夜間',
            'rate' => 200,
        ]);
        $this->assertDatabaseHas('parking_spot_rates', [
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '全日',
            'rate' => 50,
        ]);
        $this->assertDatabaseCount('parking_spot_rates', 2);
    }

    public function test_parking_spot_update_replaces_rates_from_confirmed_form(): void
    {
        [$parkingSpot, $user, $postalcode] = $this->createParkingSpot();

        ParkingSpotRates::create([
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '平日',
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'unit_minutes' => 30,
            'rate' => 100,
            'free_minutes' => 0,
            'max_rate' => 1200,
        ]);

        $input = [
            'id' => $parkingSpot->id,
            'name' => '更新Featureテスト駐車場',
            'postalcode' => $postalcode->postalcode,
            'address' => '東京都千代田区千代田1-3',
            'longitude' => 139.754000,
            'latitude' => 35.686000,
            'capacity' => 1,
            'opening_time' => '00:00',
            'closing_time' => '00:00',
            'rates' => [
                $this->validRateInput([
                    'day_type' => '土日祝',
                    'start_time' => '09:00',
                    'end_time' => '18:00',
                    'unit_minutes' => 60,
                    'rate' => 300,
                    'free_minutes' => 30,
                    'max_rate' => 1800,
                ]),
            ],
        ];

        $response = $this->actingAs($user)
            ->withSession(['edit_parking_spot_form' => $input])
            ->post(route('parking_spot.update'));

        $response->assertRedirect(route('home'));
        $this->assertDatabaseMissing('parking_spot_rates', [
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '平日',
        ]);
        $this->assertDatabaseHas('parking_spot_rates', [
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '土日祝',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'unit_minutes' => 60,
            'rate' => 300,
            'free_minutes' => 30,
            'max_rate' => 1800,
        ]);
        $this->assertDatabaseCount('parking_spot_rates', 1);
    }

    public function test_parking_spot_rate_validation_requires_at_least_one_rate(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'rates' => [],
            ]));

        $response->assertRedirect(route('parking_spot.create'));
        $response->assertSessionHasErrors(['rates']);
    }

    public function test_parking_spot_rate_validation_limits_rates_to_four(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'rates' => array_fill(0, 5, $this->validRateInput()),
            ]));

        $response->assertRedirect(route('parking_spot.create'));
        $response->assertSessionHasErrors(['rates']);
    }

    public function test_parking_spot_rate_validation_requires_max_rate_when_no_max_rate_is_off(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'rates' => [
                    $this->validRateInput([
                        'max_rate' => '',
                    ]),
                ],
            ]));

        $response->assertRedirect(route('parking_spot.create'));
        $response->assertSessionHasErrors(['rates.0.max_rate']);
    }

    public function test_parking_spot_rate_validation_allows_empty_max_rate_when_no_max_rate_is_on(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'rates' => [
                    $this->validRateInput([
                        'max_rate' => '',
                        'no_max_rate' => '1',
                    ]),
                ],
            ]));

        $response->assertOk();
        $response->assertSessionMissing('errors');
    }

    public function test_parking_spot_rate_validation_rejects_invalid_rate_fields(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'rates' => [
                    [
                        'day_type' => '祝前日',
                        'start_time' => '8時',
                        'end_time' => '20:00',
                        'unit_minutes' => 0,
                        'rate' => -1,
                        'free_minutes' => -1,
                        'max_rate' => -1,
                    ],
                ],
            ]));

        $response->assertRedirect(route('parking_spot.create'));
        $response->assertSessionHasErrors([
            'rates.0.day_type',
            'rates.0.start_time',
            'rates.0.unit_minutes',
            'rates.0.rate',
            'rates.0.free_minutes',
            'rates.0.max_rate',
        ]);
    }

    public function test_parking_spot_rate_validation_rejects_unconfigured_unit_minutes(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'rates' => [
                    $this->validRateInput([
                        'unit_minutes' => 7,
                    ]),
                ],
            ]));

        $response->assertRedirect(route('parking_spot.create'));
        $response->assertSessionHasErrors(['rates.0.unit_minutes']);
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

        $user = User::create([
            'user_id' => 'rate-user',
            'name' => 'Rate User',
            'password' => Hash::make('password'),
            'prefecture_id' => $prefecture->id,
        ]);

        $parkingSpot = ParkingSpot::forceCreate([
            'user_id' => $user->id,
            'name' => '料金表示テスト駐車場',
            'postalcode' => $postalcode->id,
            'address' => '東京都千代田区千代田1-1',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'opening_time' => '00:00:00',
            'closing_time' => '00:00:00',
        ]);

        return [$parkingSpot, $user, $postalcode];
    }

    private function validParkingSpotInput(Postalcode $postalcode, array $overrides = []): array
    {
        $input = [
            'name' => '料金バリデーションテスト駐車場',
            'postalcode' => $postalcode->postalcode,
            'address1' => '東京都千代田区千代田',
            'address2' => '1-2',
            'capacity' => 1,
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
}
