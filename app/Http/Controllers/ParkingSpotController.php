<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParkingSpotRequest;
use App\Services\ParkingSpotService;
use Illuminate\Http\Request;

class ParkingSpotController extends Controller
{
    public function __construct()
    {
        $this->service = new ParkingSpotService;
    }

    public function create()
    {
        $capacity = config('categories.parking_spot_capacity');

        return view('parking_spot.create', compact('capacity'));
    }

    public function confirm(ParkingSpotRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['address'] = mb_convert_kana($validatedData['address1'].$validatedData['address2'], 'rn');
        $validatedData['postalcode'] = mb_convert_kana(str_replace('-', '', $validatedData['postalcode']), 'rn');

        $yolpLocation = $this->service->getYolpLonLat($validatedData['address']);

        if (is_null($yolpLocation)) {
            return redirect()->route('parking_spot.create')->withErrors(['address2' => '住所が見つかりません。'])->withInput();
        }

        $validatedData['longitude'] = $yolpLocation['lon'];
        $validatedData['latitude'] = $yolpLocation['lat'];
        $validatedData['address'] = $yolpLocation['address'];

        $capacity = config('categories.parking_spot_capacity');

        return view('parking_spot.confirm', compact('validatedData', 'capacity'));
    }

    public function store(ParkingSpotRequest $request)
    {
        $this->service->saveParkingSpot($request);
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', '駐車場を登録しました。');
    }

    public function createBack(ParkingSpotRequest $request)
    {
        return redirect()->route('parking_spot.create')->withInput();
    }
}
