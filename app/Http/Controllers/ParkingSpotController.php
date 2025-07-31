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
        $validatedData['address'] = $validatedData['address1'].$validatedData['address2'];

        $capacity = config('categories.parking_spot_capacity');

        return view('parking_spot.confirm', compact('validatedData', 'capacity'));
    }

    public function store(ParkingSpotRequest $request)
    {
        $yolpLocation = $this->service->getYolpLonLat($request->input('address'));

        $request->merge([
            'longitude' => $yolpLocation['lon'],
            'latitude' => $yolpLocation['lat'],
        ]);

        $this->service->saveParkingSpot($request);

        return redirect()->route('home')->with('success', '駐車場を登録しました。');
    }

    public function createBack(Request $request)
    {
        return redirect()->route('parking_spot.create')->withInput();
    }
}
