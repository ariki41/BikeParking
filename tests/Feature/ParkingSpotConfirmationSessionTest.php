<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use App\Services\ParkingSpotConfirmationService;
use App\Services\ParkingSpotPersistenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class ParkingSpotConfirmationSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_and_update_without_confirmation_session_redirect_safely(): void
    {
        [$parkingSpot, $user] = $this->createParkingSpot();

        $this->actingAs($user)
            ->post(route('parking_spot.store'))
            ->assertRedirect(route('parking_spot.create'))
            ->assertSessionHasErrors(['confirmation']);

        $this->post(route('parking_spot.update'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('parking_spots', 1);
        $this->assertSame('確認セッションテスト駐輪場', $parkingSpot->fresh()->name);
    }

    public function test_edit_confirmation_rejects_a_tampered_parking_spot_id(): void
    {
        [$parkingSpot, $user, $postalcode] = $this->createParkingSpot();
        $otherParkingSpot = ParkingSpot::forceCreate([
            'user_id' => $user->id,
            'name' => '改ざん先の駐輪場',
            'postalcode' => $postalcode->id,
            'address' => '東京都千代田区千代田2-1',
            'longitude' => 139.754000,
            'latitude' => 35.686000,
            'capacity' => 2,
            'opening_time' => '00:00:00',
            'closing_time' => '00:00:00',
        ]);
        $this->fakeGeocode();

        $this->actingAs($user)
            ->get(route('parking_spot.edit', $parkingSpot->id))
            ->assertOk();

        $this->from(route('parking_spot.edit', $parkingSpot->id))
            ->post(route('parking_spot.confirm'), $this->validFormInput($postalcode, [
                'id' => $otherParkingSpot->id,
                'name' => '保存されてはいけない名称',
            ]))
            ->assertRedirect(route('parking_spot.edit', $parkingSpot->id))
            ->assertSessionHasErrors(['confirmation']);

        $this->post(route('parking_spot.update'))->assertRedirect(route('home'));

        $this->assertSame('確認セッションテスト駐輪場', $parkingSpot->fresh()->name);
        $this->assertSame('改ざん先の駐輪場', $otherParkingSpot->fresh()->name);
    }

    public function test_confirmation_rejects_untracked_temporary_and_permanent_image_paths(): void
    {
        [$parkingSpot, $user, $postalcode] = $this->createParkingSpot();
        Storage::fake('public');
        Storage::disk('public')->put('temp/parking-spots/another-session.webp', 'temporary image');
        Storage::disk('public')->put('parking-spots/another-parking-spot.webp', 'permanent image');
        $this->fakeGeocode();

        $this->actingAs($user)->get(route('parking_spot.create'))->assertOk();

        $this->from(route('parking_spot.create'))
            ->post(route('parking_spot.confirm'), $this->validFormInput($postalcode, [
                'image_path' => 'temp/parking-spots/another-session.webp',
            ]))
            ->assertRedirect(route('parking_spot.create'))
            ->assertSessionHasErrors(['image_paths']);

        $this->get(route('parking_spot.edit', $parkingSpot->id))->assertOk();

        $this->from(route('parking_spot.edit', $parkingSpot->id))
            ->post(route('parking_spot.confirm'), $this->validFormInput($postalcode, [
                'id' => $parkingSpot->id,
                'image_path' => 'parking-spots/another-parking-spot.webp',
            ]))
            ->assertRedirect(route('parking_spot.edit', $parkingSpot->id))
            ->assertSessionHasErrors(['image_paths']);

        $this->assertNull($parkingSpot->fresh()->image_path);
    }

    public function test_back_navigation_keeps_confirmation_and_successful_submission_is_single_use(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();
        $input = $this->validFormInput($postalcode, ['name' => '戻る操作テスト駐輪場']);
        $this->fakeGeocode();

        $this->assertSame(10, Route::getRoutes()->getByName('parking_spot.store')->locksFor());
        $this->assertSame(10, Route::getRoutes()->getByName('parking_spot.update')->locksFor());

        $this->actingAs($user)->get(route('parking_spot.create'))->assertOk();

        $this->post(route('parking_spot.confirm'), $input)->assertOk();

        $this->post(route('parking_spot.store'), ['back' => 'back'])
            ->assertRedirect(route('parking_spot.create'))
            ->assertSessionHas(ParkingSpotConfirmationService::SESSION_KEY.'.input.name', '戻る操作テスト駐輪場');

        $this->post(route('parking_spot.confirm'), $input)->assertOk();

        $this->post(route('parking_spot.store'))
            ->assertRedirect(route('home'))
            ->assertSessionMissing(ParkingSpotConfirmationService::SESSION_KEY);

        $this->post(route('parking_spot.store'))
            ->assertRedirect(route('parking_spot.create'))
            ->assertSessionHasErrors(['confirmation']);

        $this->assertSame(1, ParkingSpot::where('name', '戻る操作テスト駐輪場')->count());
    }

    public function test_edit_back_navigation_keeps_the_trusted_target_for_retry(): void
    {
        [$parkingSpot, $user, $postalcode] = $this->createParkingSpot();
        $input = $this->validFormInput($postalcode, [
            'id' => $parkingSpot->id,
            'name' => '戻って再試行した駐輪場',
        ]);
        $this->fakeGeocode();

        $this->actingAs($user)->get(route('parking_spot.edit', $parkingSpot->id))->assertOk();
        $this->post(route('parking_spot.confirm'), $input)->assertOk();

        $this->post(route('parking_spot.update'), ['back' => 'back'])
            ->assertRedirect(route('parking_spot.edit', $parkingSpot->id))
            ->assertSessionHas(ParkingSpotConfirmationService::SESSION_KEY.'.parking_spot_id', $parkingSpot->id)
            ->assertSessionHas(ParkingSpotConfirmationService::SESSION_KEY.'.input.name', '戻って再試行した駐輪場');

        $this->post(route('parking_spot.confirm'), $input)->assertOk();
        $this->post(route('parking_spot.update'))
            ->assertRedirect(route('home'))
            ->assertSessionMissing(ParkingSpotConfirmationService::SESSION_KEY);

        $this->assertSame('戻って再試行した駐輪場', $parkingSpot->fresh()->name);
    }

    public function test_validation_failure_keeps_confirmed_input_for_retry(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();
        $input = $this->validFormInput($postalcode, ['name' => '再試行テスト駐輪場']);
        $this->fakeGeocode();

        $this->actingAs($user)->get(route('parking_spot.create'))->assertOk();
        $this->post(route('parking_spot.confirm'), $input)->assertOk();

        $service = Mockery::mock(ParkingSpotPersistenceService::class);
        $service->shouldReceive('create')
            ->once()
            ->andThrow(ValidationException::withMessages(['postalcode' => '保存できませんでした。']));
        $this->app->instance(ParkingSpotPersistenceService::class, $service);

        $this->post(route('parking_spot.store'))
            ->assertRedirect(route('parking_spot.create'))
            ->assertSessionHasErrors(['postalcode'])
            ->assertSessionHas(ParkingSpotConfirmationService::SESSION_KEY.'.input.name', '再試行テスト駐輪場');

        $this->assertDatabaseMissing('parking_spots', ['name' => '再試行テスト駐輪場']);
    }

    public function test_expired_confirmation_is_rejected_before_a_missing_temporary_image_is_used(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();
        $input = [
            ...$this->validFormInput($postalcode, ['name' => '期限切れテスト駐輪場']),
            'address' => '東京都千代田区千代田1-2',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'image_paths' => ['temp/parking-spots/already-pruned.webp'],
            'image_path' => 'temp/parking-spots/already-pruned.webp',
        ];

        $this->actingAs($user)
            ->withSession([
                ParkingSpotConfirmationService::SESSION_KEY => [
                    'mode' => ParkingSpotConfirmationService::MODE_CREATE,
                    'parking_spot_id' => null,
                    'input' => $input,
                    'temporary_image_paths' => $input['image_paths'],
                    'expires_at' => now()->subMinute()->getTimestamp(),
                ],
            ])
            ->post(route('parking_spot.store'))
            ->assertRedirect(route('parking_spot.create'))
            ->assertSessionHasErrors(['confirmation'])
            ->assertSessionMissing(ParkingSpotConfirmationService::SESSION_KEY);

        $this->assertDatabaseMissing('parking_spots', ['name' => '期限切れテスト駐輪場']);
    }

    public function test_prune_command_deletes_only_expired_temporary_images(): void
    {
        Storage::fake('public');
        $disk = Storage::disk('public');
        $expiredPath = 'temp/parking-spots/expired.webp';
        $freshPath = 'temp/parking-spots/fresh.webp';
        $disk->put($expiredPath, 'expired image');
        $disk->put($freshPath, 'fresh image');
        touch($disk->path($expiredPath), now()->subHours(25)->getTimestamp());
        clearstatcache(true, $disk->path($expiredPath));

        $this->artisan('parking-spots:prune-temporary-images', ['--hours' => 24])
            ->expectsOutput('期限切れの駐輪場一時画像を1件削除しました。')
            ->assertSuccessful();

        $disk->assertMissing($expiredPath);
        $disk->assertExists($freshPath);
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
            'user_id' => 'confirmation-user',
            'name' => 'Confirmation User',
            'password' => Hash::make('password'),
            'prefecture_id' => $prefecture->id,
        ]);
        $parkingSpot = ParkingSpot::forceCreate([
            'user_id' => $user->id,
            'name' => '確認セッションテスト駐輪場',
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

    private function validFormInput(Postalcode $postalcode, array $overrides = []): array
    {
        return array_replace([
            'name' => '確認セッションフォーム駐輪場',
            'postalcode' => $postalcode->postalcode,
            'address1' => '東京都千代田区千代田',
            'address2' => '1-2',
            'capacity' => 1,
            'opening_time' => '00:00',
            'closing_time' => '00:00',
            'rates' => [[
                'day_type' => '全日',
                'start_time' => '00:00',
                'end_time' => '00:00',
                'unit_minutes' => 30,
                'rate' => 100,
                'free_minutes' => 0,
                'max_rate' => 1200,
            ]],
        ], $overrides);
    }

    private function fakeGeocode(): void
    {
        Http::fake([
            '*' => Http::response([
                'Feature' => [[
                    'Geometry' => ['Coordinates' => '139.753000,35.685000'],
                    'Property' => ['Address' => '東京都千代田区千代田1-2'],
                ]],
            ]),
        ]);
    }
}
