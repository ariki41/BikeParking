<?php

namespace Tests\Unit\Services;

use App\Models\ParkingSpot;
use App\Services\ParkingSpotImageService;
use App\ValueObjects\PersistedParkingSpotImages;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParkingSpotImageServiceTest extends TestCase
{
    public function test_prepare_for_confirmation_converts_uploads_to_temporary_webp_images(): void
    {
        Storage::fake('public');
        $request = Request::create(
            '/parking-spot/confirm',
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
