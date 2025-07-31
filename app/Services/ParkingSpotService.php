<?php

namespace App\Services;

use App\Models\ParkingSpot;
use App\Models\Postalcode;
use Illuminate\Support\Facades\Http;

class ParkingSpotService
{
    public function saveParkingSpot($request)
    {
        $postalcode = Postalcode::getPostalcodeId($request->postalcode)->first()->id ?? null;
        if (! $postalcode) {
            return redirect()->route('parking_spot.create')->withErrors(['postalcode' => '郵便番号に対応する住所が見つかりません。']);
        }
        $request->merge(['postalcode' => $postalcode]);

        $parkingSpot = new ParkingSpot;
        $parkingSpot->user_id = auth()->id();
        $parkingSpot->name = $request->input('name');
        $parkingSpot->postalcode = $request->input('postalcode');
        $parkingSpot->address = $request->input('address');
        $parkingSpot->longitude = $request->input('longitude');
        $parkingSpot->latitude = $request->input('latitude');
        $parkingSpot->opening_time = $request->input('opening_time');
        $parkingSpot->closing_time = $request->input('closing_time');
        $parkingSpot->capacity = $request->input('capacity');

        $parkingSpot->save();
    }

    public function getYolpLonLat($address)
    {
        try {
            $responce = Http::timeout(5)
                ->retry(3, 100)
                ->get(env('YOLP_GEOCODE_URL'), [
                    'appid' => env('YOLP_CLIENT_ID'),
                    'query' => $address,
                    'sort' => 'score',
                    'results' => 1,
                    'output' => 'json',
                ])
                ->throw();
            $yolp = $responce->json();
        } catch (RequestException $e) {
            throw new RequestException($e->response, 'YOLP API Error');
        }

        if (isset($yolp['Feature'][0])) {
            [$lon, $lat] = explode(',', $yolp['Feature'][0]['Geometry']['Coordinates']);
            $yolpLocation = ['lon' => $lon, 'lat' => $lat];

            return $yolpLocation;
        }
    }
}
