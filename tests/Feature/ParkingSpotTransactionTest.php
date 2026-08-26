<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\ParkingSpot;
use App\Models\ParkingSpotRates;
use App\Models\Postalcode;
use App\Models\Prefecture;
use App\Models\User;
use App\Services\ParkingSpotConfirmationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ParkingSpotTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_commits_parking_spot_rates_and_image_together(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();
        Storage::fake('public');
        $tempImagePath = 'temp/parking-spots/store-success.webp';
        Storage::disk('public')->put($tempImagePath, 'new image');
        $input = $this->confirmedInput($postalcode, [
            'name' => 'トランザクション登録成功駐輪場',
            'image_paths' => [$tempImagePath],
            'image_path' => $tempImagePath,
        ]);

        $this->actingAs($user)
            ->withSession($this->confirmationState(ParkingSpotConfirmationService::MODE_CREATE, $input))
            ->post(route('parking_spot.store'))
            ->assertRedirect(route('home'))
            ->assertSessionMissing(ParkingSpotConfirmationService::SESSION_KEY);

        $parkingSpot = ParkingSpot::with(['images', 'rates'])
            ->where('name', 'トランザクション登録成功駐輪場')
            ->sole();

        $this->assertCount(1, $parkingSpot->images);
        $this->assertCount(1, $parkingSpot->rates);
        $this->assertSame($parkingSpot->images->first()->path, $parkingSpot->image_path);
        Storage::disk('public')->assertExists($parkingSpot->image_path);
        Storage::disk('public')->assertMissing($tempImagePath);
    }

    public function test_store_rolls_back_database_and_copied_image_when_a_rate_save_fails(): void
    {
        [, $user, $postalcode] = $this->createParkingSpot();
        Storage::fake('public');
        $tempImagePath = 'temp/parking-spots/store-failure.webp';
        Storage::disk('public')->put($tempImagePath, 'retryable image');
        $input = $this->confirmedInput($postalcode, [
            'name' => 'トランザクション登録失敗駐輪場',
            'image_paths' => [$tempImagePath],
            'image_path' => $tempImagePath,
            'rates' => [$this->validRateInput(), $this->invalidRateInput()],
        ]);

        $exception = $this->captureException(function () use ($user, $input): void {
            $this->withoutExceptionHandling()
                ->actingAs($user)
                ->withSession($this->confirmationState(ParkingSpotConfirmationService::MODE_CREATE, $input))
                ->post(route('parking_spot.store'));
        });

        $this->assertInstanceOf(QueryException::class, $exception);
        $this->assertDatabaseMissing('parking_spots', [
            'name' => 'トランザクション登録失敗駐輪場',
        ]);
        Storage::disk('public')->assertExists($tempImagePath);
        $this->assertSame([], Storage::disk('public')->allFiles('parking-spots'));
    }

    public function test_update_commits_parking_spot_rates_images_and_history_together(): void
    {
        [$parkingSpot, $user, $postalcode] = $this->createParkingSpot();
        Storage::fake('public');
        $oldImagePath = 'parking-spots/update-success-old.webp';
        $tempImagePath = 'temp/parking-spots/update-success-new.webp';
        Storage::disk('public')->put($oldImagePath, 'old image');
        Storage::disk('public')->put($tempImagePath, 'new image');
        $this->setImage($parkingSpot, $oldImagePath);
        $this->setRate($parkingSpot);
        $input = $this->confirmedInput($postalcode, [
            'id' => $parkingSpot->id,
            'name' => 'トランザクション更新成功駐輪場',
            'image_paths' => [$tempImagePath],
            'image_path' => $tempImagePath,
            'rates' => [$this->validRateInput(['rate' => 200, 'max_rate' => 1800])],
        ]);

        $this->actingAs($user)
            ->withSession($this->confirmationState(ParkingSpotConfirmationService::MODE_EDIT, $input))
            ->put(route('parking_spot.update', $parkingSpot))
            ->assertRedirect(route('home'))
            ->assertSessionMissing(ParkingSpotConfirmationService::SESSION_KEY);

        $parkingSpot->refresh()->load(['images', 'rates', 'updateHistories']);
        $this->assertSame('トランザクション更新成功駐輪場', $parkingSpot->name);
        $this->assertSame(200, $parkingSpot->rates->sole()->rate);
        $this->assertCount(1, $parkingSpot->images);
        $this->assertCount(1, $parkingSpot->updateHistories);
        Storage::disk('public')->assertExists($parkingSpot->image_path);
        Storage::disk('public')->assertMissing([$oldImagePath, $tempImagePath]);
    }

    public function test_update_rolls_back_database_and_preserves_images_when_a_rate_save_fails(): void
    {
        [$parkingSpot, $user, $postalcode] = $this->createParkingSpot();
        Storage::fake('public');
        $oldImagePath = 'parking-spots/update-failure-old.webp';
        $tempImagePath = 'temp/parking-spots/update-failure-new.webp';
        Storage::disk('public')->put($oldImagePath, 'old image');
        Storage::disk('public')->put($tempImagePath, 'retryable image');
        $this->setImage($parkingSpot, $oldImagePath);
        $this->setRate($parkingSpot);
        $input = $this->confirmedInput($postalcode, [
            'id' => $parkingSpot->id,
            'name' => '保存されてはいけない駐輪場名',
            'image_paths' => [$tempImagePath],
            'image_path' => $tempImagePath,
            'rates' => [$this->validRateInput(['rate' => 200]), $this->invalidRateInput()],
        ]);

        $exception = $this->captureException(function () use ($parkingSpot, $user, $input): void {
            $this->withoutExceptionHandling()
                ->actingAs($user)
                ->withSession($this->confirmationState(ParkingSpotConfirmationService::MODE_EDIT, $input))
                ->put(route('parking_spot.update', $parkingSpot));
        });

        $this->assertInstanceOf(QueryException::class, $exception);
        $parkingSpot->refresh()->load(['images', 'rates', 'updateHistories']);
        $this->assertSame('トランザクションテスト駐輪場', $parkingSpot->name);
        $this->assertSame($oldImagePath, $parkingSpot->image_path);
        $this->assertSame([$oldImagePath], $parkingSpot->images->pluck('path')->all());
        $this->assertSame(100, $parkingSpot->rates->sole()->rate);
        $this->assertCount(0, $parkingSpot->updateHistories);
        Storage::disk('public')->assertExists([$oldImagePath, $tempImagePath]);
        $this->assertSame([$oldImagePath], Storage::disk('public')->allFiles('parking-spots'));
    }

    public function test_missing_temporary_image_leaves_existing_update_data_untouched(): void
    {
        [$parkingSpot, $user, $postalcode] = $this->createParkingSpot();
        Storage::fake('public');
        $oldImagePath = 'parking-spots/missing-image-old.webp';
        $missingTempImagePath = 'temp/parking-spots/missing.webp';
        Storage::disk('public')->put($oldImagePath, 'old image');
        $this->setImage($parkingSpot, $oldImagePath);
        $this->setRate($parkingSpot);
        $input = $this->confirmedInput($postalcode, [
            'id' => $parkingSpot->id,
            'name' => '保存されてはいけない画像失敗名',
            'image_paths' => [$missingTempImagePath],
            'image_path' => $missingTempImagePath,
            'rates' => [$this->validRateInput(['rate' => 200])],
        ]);

        $exception = $this->captureException(function () use ($parkingSpot, $user, $input): void {
            $this->withoutExceptionHandling()
                ->actingAs($user)
                ->withSession($this->confirmationState(ParkingSpotConfirmationService::MODE_EDIT, $input))
                ->put(route('parking_spot.update', $parkingSpot));
        });

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $parkingSpot->refresh()->load(['images', 'rates', 'updateHistories']);
        $this->assertSame('トランザクションテスト駐輪場', $parkingSpot->name);
        $this->assertSame($oldImagePath, $parkingSpot->image_path);
        $this->assertSame([$oldImagePath], $parkingSpot->images->pluck('path')->all());
        $this->assertSame(100, $parkingSpot->rates->sole()->rate);
        $this->assertCount(0, $parkingSpot->updateHistories);
        Storage::disk('public')->assertExists($oldImagePath);
    }

    public function test_image_copy_failure_rolls_back_update_and_keeps_source_images(): void
    {
        [$parkingSpot, $user, $postalcode] = $this->createParkingSpot();
        $disk = Storage::fake('public');
        $oldImagePath = 'parking-spots/copy-failure-old.webp';
        $tempImagePath = 'temp/parking-spots/copy-failure-new.webp';
        $disk->put($oldImagePath, 'old image');
        $disk->put($tempImagePath, 'retryable image');
        $this->setImage($parkingSpot, $oldImagePath);
        $this->setRate($parkingSpot);
        $input = $this->confirmedInput($postalcode, [
            'id' => $parkingSpot->id,
            'name' => '保存されてはいけないコピー失敗名',
            'image_paths' => [$tempImagePath],
            'image_path' => $tempImagePath,
            'rates' => [$this->validRateInput(['rate' => 200])],
        ]);

        $failingDisk = Mockery::mock($disk)->makePartial();
        $failingDisk->shouldReceive('copy')->once()->andReturnFalse();
        Storage::set('public', $failingDisk);

        $exception = $this->captureException(function () use ($parkingSpot, $user, $input): void {
            $this->withoutExceptionHandling()
                ->actingAs($user)
                ->withSession($this->confirmationState(ParkingSpotConfirmationService::MODE_EDIT, $input))
                ->put(route('parking_spot.update', $parkingSpot));
        });

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $parkingSpot->refresh()->load(['images', 'rates', 'updateHistories']);
        $this->assertSame('トランザクションテスト駐輪場', $parkingSpot->name);
        $this->assertSame([$oldImagePath], $parkingSpot->images->pluck('path')->all());
        $this->assertSame(100, $parkingSpot->rates->sole()->rate);
        $this->assertCount(0, $parkingSpot->updateHistories);
        $disk->assertExists([$oldImagePath, $tempImagePath]);
    }

    public function test_file_cleanup_failure_keeps_committed_update_data_consistent(): void
    {
        [$parkingSpot, $user, $postalcode] = $this->createParkingSpot();
        $disk = Storage::fake('public');
        $oldImagePath = 'parking-spots/delete-failure-old.webp';
        $tempImagePath = 'temp/parking-spots/delete-failure-new.webp';
        $disk->put($oldImagePath, 'old image');
        $disk->put($tempImagePath, 'new image');
        $this->setImage($parkingSpot, $oldImagePath);
        $this->setRate($parkingSpot);
        $input = $this->confirmedInput($postalcode, [
            'id' => $parkingSpot->id,
            'name' => '画像削除失敗後も整合する駐輪場',
            'image_paths' => [$tempImagePath],
            'image_path' => $tempImagePath,
            'rates' => [$this->validRateInput(['rate' => 200])],
        ]);

        $failingDisk = Mockery::mock($disk)->makePartial();
        $failingDisk->shouldReceive('delete')->twice()->andReturnFalse();
        Storage::set('public', $failingDisk);

        $this->actingAs($user)
            ->withSession($this->confirmationState(ParkingSpotConfirmationService::MODE_EDIT, $input))
            ->put(route('parking_spot.update', $parkingSpot))
            ->assertRedirect(route('home'));

        $parkingSpot->refresh()->load(['images', 'rates', 'updateHistories']);
        $this->assertSame('画像削除失敗後も整合する駐輪場', $parkingSpot->name);
        $this->assertNotSame($oldImagePath, $parkingSpot->image_path);
        $this->assertSame([$parkingSpot->image_path], $parkingSpot->images->pluck('path')->all());
        $this->assertSame(200, $parkingSpot->rates->sole()->rate);
        $this->assertCount(1, $parkingSpot->updateHistories);
        $disk->assertExists([$oldImagePath, $tempImagePath, $parkingSpot->image_path]);
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
            'user_id' => 'transaction-user',
            'name' => 'Transaction User',
            'password' => Hash::make('password'),
            'prefecture_id' => $prefecture->id,
        ]);
        $parkingSpot = ParkingSpot::forceCreate([
            'user_id' => $user->id,
            'name' => 'トランザクションテスト駐輪場',
            'postalcode_id' => $postalcode->id,
            'address' => '東京都千代田区千代田1-1',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'opening_time' => '00:00:00',
            'closing_time' => '00:00:00',
        ]);

        return [$parkingSpot, $user, $postalcode];
    }

    private function setImage(ParkingSpot $parkingSpot, string $path): void
    {
        $parkingSpot->images()->create([
            'path' => $path,
            'position' => 0,
        ]);
        $parkingSpot->forceFill(['image_path' => $path])->save();
    }

    private function setRate(ParkingSpot $parkingSpot): void
    {
        ParkingSpotRates::create([
            'parking_spot_id' => $parkingSpot->id,
            ...$this->validRateInput(),
        ]);
    }

    private function confirmedInput(Postalcode $postalcode, array $overrides = []): array
    {
        return array_replace([
            'name' => 'トランザクションテスト駐輪場',
            'postalcode' => $postalcode->postalcode,
            'address' => '東京都千代田区千代田1-2',
            'longitude' => 139.753000,
            'latitude' => 35.685000,
            'capacity' => 1,
            'opening_time' => '00:00',
            'closing_time' => '00:00',
            'rates' => [$this->validRateInput()],
        ], $overrides);
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

    private function invalidRateInput(): array
    {
        return $this->validRateInput(['day_type' => null]);
    }

    private function captureException(callable $callback): ?\Throwable
    {
        try {
            $callback();
        } catch (\Throwable $exception) {
            return $exception;
        }

        return null;
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
