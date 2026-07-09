<?php

namespace App\Services;

use App\Models\ParkingSpot;
use App\Models\Postalcode;
use Illuminate\Support\Facades\Http;

class ParkingSpotService
{
    public function saveParkingSpot($input)
    {
        $postalcodeId = Postalcode::getPostalcodeId($input['postalcode'])->first()->id ?? null;
        if (! $postalcodeId) {
            return redirect()->route('parking_spot.create')->withErrors(['postalcode' => '郵便番号に対応する住所が見つかりません。']);
        }

        $parkingSpot = new ParkingSpot;
        $parkingSpot->user_id = auth()->id();
        $parkingSpot->name = $input['name'];
        $parkingSpot->postalcode = $postalcodeId;
        $parkingSpot->address = $input['address'];
        $parkingSpot->longitude = $input['longitude'];
        $parkingSpot->latitude = $input['latitude'];
        $parkingSpot->opening_time = $input['opening_time'];
        $parkingSpot->closing_time = $input['closing_time'];
        $parkingSpot->capacity = $input['capacity'];

        $parkingSpot->save();
    }

    public function updateParkingSpot($input)
    {
        $id = $input['id'];
        $parkingSpot = ParkingSpot::findOrFail($id);

        $postalcode = Postalcode::getPostalcodeId($input['postalcode'])->first()->id ?? null;
        if (! $postalcode) {
            return redirect()->route('parking_spot.edit', ['id' => $id])->withErrors(['postalcode' => '郵便番号に対応する住所が見つかりません。']);
        }

        $parkingSpot->name = $input['name'];
        $parkingSpot->postalcode = $postalcode;
        $parkingSpot->address = $input['address'];
        $parkingSpot->longitude = $input['longitude'];
        $parkingSpot->latitude = $input['latitude'];
        $parkingSpot->opening_time = $input['opening_time'];
        $parkingSpot->closing_time = $input['closing_time'];
        $parkingSpot->capacity = $input['capacity'];

        $parkingSpot->save();

        session()->forget('parking_spot_form');
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
            $address = $yolp['Feature'][0]['Property']['Address'];
            $yolpLocation = ['lon' => $lon, 'lat' => $lat, 'address' => $address];

            return $yolpLocation;
        }
    }
}
