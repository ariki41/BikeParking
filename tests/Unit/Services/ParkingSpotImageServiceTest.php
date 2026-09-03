<?php

namespace Tests\Unit\Services;

use App\Models\ParkingSpot;
use App\Services\ParkingSpotImageService;
use App\ValueObjects\PersistedParkingSpotImages;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ParkingSpotImageServiceTest extends TestCase
{
    public function test_prepare_for_confirmation_converts_uploads_to_temporary_webp_images(): void
    {
        Storage::fake('public');
        $request = Request::create(
            '/parking-spots/confirm',
            'POST',
            [],
            [],
            ['images' => [UploadedFile::fake()->image('parking-spot.jpg')]],
        );

        $paths = app(ParkingSpotImageService::class)->prepareForConfirmation($request, []);

        $this->assertCount(1, $paths);
        $this->assertStringStartsWith('temp/parking-spots/', $paths[0]);
        $this->assertStringEndsWith('.webp', $paths[0]);
        Storage::disk('public')->assertExists($paths[0]);
    }

    public function test_prepare_for_confirmation_appends_uploads_after_allowed_existing_images(): void
    {
        Storage::fake('public');
        $existingPaths = [
            'parking-spots/existing-1.webp',
            'parking-spots/existing-2.webp',
        ];
        $request = Request::create(
            '/parking-spots/confirm',
            'POST',
            [],
            [],
            ['images' => [UploadedFile::fake()->image('parking-spot.jpg')]],
        );

        $paths = app(ParkingSpotImageService::class)->prepareForConfirmation(
            $request,
            $existingPaths,
            $existingPaths,
        );

        $this->assertCount(3, $paths);
        $this->assertSame($existingPaths, array_slice($paths, 0, 2));
        $this->assertStringStartsWith('temp/parking-spots/', $paths[2]);
        Storage::disk('public')->assertExists($paths[2]);
    }

    public function test_prepare_for_confirmation_rejects_an_untrusted_existing_path_when_uploading(): void
    {
        Storage::fake('public');
        $request = Request::create(
            '/parking-spots/confirm',
            'POST',
            [],
            [],
            ['images' => [UploadedFile::fake()->image('parking-spot.jpg')]],
        );

        $this->expectException(ValidationException::class);

        app(ParkingSpotImageService::class)->prepareForConfirmation(
            $request,
            ['parking-spots/untrusted.webp'],
        );
    }

    public function test_persist_confirmed_images_returns_paths_needed_by_transaction_compensation(): void
    {
        Storage::fake('public');
        $temporaryPath = 'temp/parking-spots/confirmed.webp';
        Storage::disk('public')->put($temporaryPath, 'image');
        $parkingSpot = new ParkingSpot;
        $parkingSpot->id = 42;

        $result = app(ParkingSpotImageService::class)->persistConfirmedImages(
            $parkingSpot,
            [$temporaryPath],
        );

        $this->assertInstanceOf(PersistedParkingSpotImages::class, $result);
        $this->assertSame([$temporaryPath], $result->temporaryPaths);
        $this->assertSame($result->paths, $result->createdPaths);
        $this->assertStringStartsWith('parking-spots/42_', $result->paths[0]);
        Storage::disk('public')->assertExists([$temporaryPath, $result->paths[0]]);
    }
}
