<?php

namespace Tests\Feature;

use App\Livewire\ParkingSpots;
use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\ParkingSpotRates;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use App\Services\ParkingSpotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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
        $response->assertSee('00:00 ～ 24:00');
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
        $response->assertSee('/images/noimage.jpg');
    }

    public function test_parking_spot_confirm_stores_uploaded_image_path_in_session(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();
        Storage::fake('public');
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
                'image' => UploadedFile::fake()->image('parking-spot.jpg'),
            ]));

        $response->assertOk();
        $imagePath = session('create_parking_spot_form.image_path');
        $this->assertNotNull($imagePath);
        $this->assertStringStartsWith('temp/parking-spots/', $imagePath);
        Storage::disk('public')->assertExists($imagePath);
        $response->assertSee('/storage/'.$imagePath);
    }

    public function test_parking_spot_confirm_rejects_unsupported_image_extension(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'image' => UploadedFile::fake()->create('parking-spot.gif', 100, 'image/gif'),
            ]));

        $response->assertRedirect(route('parking_spot.create'));
        $response->assertSessionHasErrors(['image']);
    }

    public function test_parking_spot_confirm_rejects_oversized_image(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'image' => UploadedFile::fake()->image('parking-spot.jpg')->size(21000),
            ]));

        $response->assertRedirect(route('parking_spot.create'));
        $response->assertSessionHasErrors(['image']);
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
        Storage::fake('public');
        $tempImagePath = UploadedFile::fake()->image('confirmed.jpg')->store('temp/parking-spots', 'public');

        $input = [
            'name' => '登録Featureテスト駐車場',
            'postalcode' => $postalcode->postalcode,
            'address' => '東京都千代田区千代田1-2',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'image_path' => $tempImagePath,
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
        $parkingSpot = ParkingSpot::where('name', '登録Featureテスト駐車場')->firstOrFail();
        $this->assertNotNull($parkingSpot->image_path);
        $this->assertMatchesRegularExpression(
            '/^parking-spots\/'.$parkingSpot->id.'_\d{17}\.webp$/',
            $parkingSpot->image_path
        );
        Storage::disk('public')->assertExists($parkingSpot->image_path);
        Storage::disk('public')->assertMissing($tempImagePath);
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

    public function test_full_day_rate_is_displayed_as_midnight_to_24_on_confirm(): void
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
                        'start_time' => '00:00',
                        'end_time' => '00:00',
                    ]),
                ],
            ]));

        $response->assertOk();
        $response->assertSessionMissing('errors');
        $response->assertSee('00:00 ～ 24:00');
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
        [$parkingSpot, $user] = $this->createParkingSpot();

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
        ], $user);

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
        Storage::fake('public');
        Storage::disk('public')->put('parking-spots/original.jpg', 'original');
        $parkingSpot->forceFill(['image_path' => 'parking-spots/original.jpg'])->save();
        $tempImagePath = UploadedFile::fake()->image('updated.jpg')->store('temp/parking-spots', 'public');

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
            'image_path' => $tempImagePath,
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
        $parkingSpot->refresh();
        $this->assertNotSame('parking-spots/original.jpg', $parkingSpot->image_path);
        $this->assertMatchesRegularExpression(
            '/^parking-spots\/'.$parkingSpot->id.'_\d{17}\.webp$/',
            $parkingSpot->image_path
        );
        Storage::disk('public')->assertMissing('parking-spots/original.jpg');
        Storage::disk('public')->assertMissing($tempImagePath);
        Storage::disk('public')->assertExists($parkingSpot->image_path);
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

    public function test_parking_spot_rate_validation_rejects_overlapping_ranges_with_same_day_type(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'rates' => [
                    $this->validRateInput([
                        'day_type' => '平日',
                        'start_time' => '08:00',
                        'end_time' => '12:00',
                    ]),
                    $this->validRateInput([
                        'day_type' => '平日',
                        'start_time' => '11:00',
                        'end_time' => '15:00',
                    ]),
                ],
            ]));

        $response->assertOk();
        $response->assertSee('料金帯1の「平日」と料金帯2の「平日」は適用条件が重複しています。');
    }

    public function test_parking_spot_rate_validation_rejects_full_day_and_weekday_full_day_combination(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'rates' => [
                    $this->validRateInput([
                        'day_type' => '全日',
                        'start_time' => '00:00',
                        'end_time' => '00:00',
                    ]),
                    $this->validRateInput([
                        'day_type' => '平日',
                        'start_time' => '00:00',
                        'end_time' => '00:00',
                    ]),
                ],
            ]));

        $response->assertOk();
        $response->assertSee('料金帯1の「全日」と料金帯2の「平日」は適用条件が重複しています。');
    }

    public function test_parking_spot_rate_validation_rejects_overlapping_ranges_with_different_broad_day_types(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'rates' => [
                    $this->validRateInput([
                        'day_type' => '昼間',
                        'start_time' => '09:00',
                        'end_time' => '14:00',
                    ]),
                    $this->validRateInput([
                        'day_type' => '夜間',
                        'start_time' => '13:00',
                        'end_time' => '18:00',
                    ]),
                ],
            ]));

        $response->assertOk();
        $response->assertSee('料金帯1の「昼間」と料金帯2の「夜間」は適用条件が重複しています。');
    }

    public function test_parking_spot_rate_validation_rejects_overlapping_ranges_between_holiday_and_broad_day_type(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'rates' => [
                    $this->validRateInput([
                        'day_type' => '土日祝',
                        'start_time' => '10:00',
                        'end_time' => '16:00',
                    ]),
                    $this->validRateInput([
                        'day_type' => '昼間',
                        'start_time' => '12:00',
                        'end_time' => '18:00',
                    ]),
                ],
            ]));

        $response->assertOk();
        $response->assertSee('料金帯1の「土日祝」と料金帯2の「昼間」は適用条件が重複しています。');
    }

    public function test_parking_spot_rate_validation_rejects_overnight_overlap_on_edit_flow(): void
    {
        [$parkingSpot, $user, $postalcode] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->from(route('parking_spot.edit', $parkingSpot->id))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'id' => $parkingSpot->id,
                'rates' => [
                    $this->validRateInput([
                        'day_type' => '夜間',
                        'start_time' => '22:00',
                        'end_time' => '06:00',
                    ]),
                    $this->validRateInput([
                        'day_type' => '夜間',
                        'start_time' => '05:30',
                        'end_time' => '09:00',
                    ]),
                ],
            ]));

        $response->assertOk();
        $response->assertSee('料金帯1の「夜間」と料金帯2の「夜間」は適用条件が重複しています。');
    }

    public function test_parking_spot_rate_validation_allows_adjacent_ranges_with_same_day_type(): void
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
                        'day_type' => '平日',
                        'start_time' => '08:00',
                        'end_time' => '12:00',
                    ]),
                    $this->validRateInput([
                        'day_type' => '平日',
                        'start_time' => '12:00',
                        'end_time' => '18:00',
                    ]),
                ],
            ]));

        $response->assertOk();
        $response->assertSessionMissing('errors');
        $response->assertSee('08:00 ～ 12:00');
        $response->assertSee('12:00 ～ 18:00');
    }

    public function test_parking_spot_rate_validation_allows_adjacent_ranges_with_different_broad_day_types(): void
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
                        'day_type' => '昼間',
                        'start_time' => '08:00',
                        'end_time' => '18:00',
                    ]),
                    $this->validRateInput([
                        'day_type' => '夜間',
                        'start_time' => '18:00',
                        'end_time' => '23:00',
                    ]),
                ],
            ]));

        $response->assertOk();
        $response->assertSessionMissing('errors');
        $response->assertSee('08:00 ～ 18:00');
        $response->assertSee('18:00 ～ 23:00');
    }

    public function test_parking_spot_rate_validation_allows_weekday_and_holiday_full_day_combination(): void
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
                        'day_type' => '平日',
                        'start_time' => '00:00',
                        'end_time' => '00:00',
                    ]),
                    $this->validRateInput([
                        'day_type' => '土日祝',
                        'start_time' => '00:00',
                        'end_time' => '00:00',
                    ]),
                ],
            ]));

        $response->assertOk();
        $response->assertSessionMissing('errors');
        $response->assertSee('00:00 ～ 24:00');
    }

    public function test_parking_spot_rate_validation_reports_only_format_error_when_time_fields_are_invalid(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'rates' => [
                    $this->validRateInput([
                        'day_type' => '全日',
                        'start_time' => '8時',
                        'end_time' => '20:00',
                    ]),
                    $this->validRateInput([
                        'day_type' => '平日',
                        'start_time' => '09:00',
                        'end_time' => '12:00',
                    ]),
                ],
            ]));

        $response->assertOk();
        $response->assertSee('料金開始時間の形式が正しくありません。例: 08:00');
        $response->assertDontSee('適用条件が重複しています。');
    }

    public function test_parking_spot_rate_validation_rejects_multiple_overlaps_from_one_full_day_rate(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validParkingSpotInput($postalcode, [
                'rates' => [
                    $this->validRateInput([
                        'day_type' => '全日',
                        'start_time' => '00:00',
                        'end_time' => '00:00',
                    ]),
                    $this->validRateInput([
                        'day_type' => '平日',
                        'start_time' => '09:00',
                        'end_time' => '18:00',
                    ]),
                    $this->validRateInput([
                        'day_type' => '土日祝',
                        'start_time' => '10:00',
                        'end_time' => '17:00',
                    ]),
                ],
            ]));

        $response->assertOk();
        $response->assertSee('料金帯1の「全日」と料金帯2の「平日」は適用条件が重複しています。');
        $response->assertSee('料金帯1の「全日」と料金帯3の「土日祝」は適用条件が重複しています。');
        $response->assertDontSee('料金帯2の「平日」と料金帯3の「土日祝」は適用条件が重複しています。');
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

    public function test_home_displays_uploaded_image_and_fallback_image(): void
    {
        [$parkingSpot, $user] = $this->createParkingSpot();
        $parkingSpot->forceFill(['image_path' => 'parking-spots/home.jpg'])->save();

        ParkingSpot::forceCreate([
            'user_id' => $user->id,
            'name' => '画像なし駐車場',
            'postalcode' => $parkingSpot->postalcode,
            'address' => '東京都千代田区千代田1-9',
            'longitude' => 139.759000,
            'latitude' => 35.689000,
            'capacity' => 2,
            'opening_time' => '00:00:00',
            'closing_time' => '00:00:00',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('/storage/parking-spots/home.jpg');
        $response->assertSee('/images/noimage.jpg');
    }

    public function test_home_displays_first_registered_rate_as_representative_with_remaining_count(): void
    {
        [$parkingSpot] = $this->createParkingSpot();

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
        ParkingSpotRates::create([
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '土日祝',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'unit_minutes' => 60,
            'rate' => 50,
            'free_minutes' => 0,
            'max_rate' => 500,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('代表料金');
        $response->assertSee('最初の30分無料 / 以降30分 100円 / 最大 1,200円');
        $response->assertSee('ほか1件の料金帯');
        $response->assertSee('平日');
        $response->assertSee('08:00 ～ 20:00');
        $response->assertDontSee('1時間 50円 / 最大 500円');
    }

    public function test_home_displays_rate_placeholder_without_rates(): void
    {
        $this->createParkingSpot();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('新着の駐輪場')
            ->assertSee('代表料金')
            ->assertSee('料金未登録');
    }

    public function test_favorites_index_displays_rate_summary(): void
    {
        [$parkingSpot, $user] = $this->createParkingSpot();
        $user->favorites()->create([
            'parking_spot_id' => $parkingSpot->id,
        ]);

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

        $this->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('代表料金')
            ->assertSee('30分 100円 / 最大 1,200円')
            ->assertSee('平日')
            ->assertSee('08:00 ～ 20:00');
    }

    public function test_livewire_parking_spots_list_displays_rate_summary(): void
    {
        [$parkingSpot] = $this->createParkingSpot();

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
        ParkingSpotRates::create([
            'parking_spot_id' => $parkingSpot->id,
            'day_type' => '夜間',
            'start_time' => '20:00:00',
            'end_time' => '08:00:00',
            'unit_minutes' => 60,
            'rate' => 200,
            'free_minutes' => 0,
            'max_rate' => 800,
        ]);

        Livewire::test(ParkingSpots::class)
            ->call('updateBounds', [
                'south' => 35.0,
                'north' => 36.0,
                'west' => 139.0,
                'east' => 140.0,
            ])
            ->assertSee('代表料金')
            ->assertSee('30分 100円 / 最大料金なし')
            ->assertSee('ほか1件の料金帯')
            ->assertSee('00:00 ～ 24:00')
            ->assertDontSee('1時間 200円 / 最大 800円');
    }

    public function test_livewire_parking_spots_list_uses_image_url_accessor(): void
    {
        [$parkingSpot] = $this->createParkingSpot();
        $parkingSpot->forceFill(['image_path' => 'parking-spots/list.jpg'])->save();

        Livewire::test(ParkingSpots::class)
            ->set('spots', collect([
                $parkingSpot->fresh()
                    ->load('representativeRate')
                    ->loadCount('rates'),
            ]))
            ->assertSee('/storage/parking-spots/list.jpg');
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
