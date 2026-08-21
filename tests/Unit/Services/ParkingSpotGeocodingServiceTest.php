<?php

namespace Tests\Unit\Services;

use App\Services\ParkingSpotGeocodingService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ParkingSpotGeocodingServiceTest extends TestCase
{
    public function test_geocode_returns_normalized_location_from_yolp_response(): void
    {
        Http::fake([
            '*' => Http::response([
                'Feature' => [[
                    'Geometry' => ['Coordinates' => '139.753000,35.685000'],
                    'Property' => ['Address' => '東京都千代田区千代田1-1'],
                ]],
            ]),
        ]);

        $location = app(ParkingSpotGeocodingService::class)->geocode('東京都千代田区千代田1-1');

        $this->assertSame([
            'lon' => '139.753000',
            'lat' => '35.685000',
            'address' => '東京都千代田区千代田1-1',
        ], $location);
        Http::assertSent(fn (Request $request) => $request['query'] === '東京都千代田区千代田1-1'
            && $request['results'] === 1
            && $request['output'] === 'json');
    }

    public function test_geocode_returns_null_when_yolp_has_no_matching_feature(): void
    {
        Http::fake(['*' => Http::response(['Feature' => []])]);

        $this->assertNull(app(ParkingSpotGeocodingService::class)->geocode('存在しない住所'));
    }
}
