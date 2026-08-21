<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class ParkingSpotGeocodingService
{
    /**
     * @return array{lon: string, lat: string, address: string}|null
     */
    public function geocode(string $address): ?array
    {
        try {
            $response = Http::timeout(5)
                ->retry(3, 100)
                ->get(env('YOLP_GEOCODE_URL'), [
                    'appid' => env('YOLP_CLIENT_ID'),
                    'query' => $address,
                    'sort' => 'score',
                    'results' => 1,
                    'output' => 'json',
                ])
                ->throw();
            $yolp = $response->json();
        } catch (RequestException $exception) {
            throw new RequestException($exception->response, 'YOLP API Error');
        }

        if (! isset($yolp['Feature'][0])) {
            return null;
        }

        [$longitude, $latitude] = explode(',', $yolp['Feature'][0]['Geometry']['Coordinates']);

        return [
            'lon' => $longitude,
            'lat' => $latitude,
            'address' => $yolp['Feature'][0]['Property']['Address'],
        ];
    }
}
