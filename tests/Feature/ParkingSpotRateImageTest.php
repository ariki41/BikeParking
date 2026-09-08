<?php

namespace Tests\Feature;

use App\Services\ParkingSpotConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\InteractsWithParkingSpotRateFixtures;
use Tests\TestCase;

class ParkingSpotRateImageTest extends TestCase
{
    use InteractsWithParkingSpotRateFixtures;
    use RefreshDatabase;

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
        $imagePath = session(ParkingSpotConfirmationService::SESSION_KEY.'.input.image_path');
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
}
