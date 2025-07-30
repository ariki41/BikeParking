<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParkingSpotRequest;
use Illuminate\Http\Request;

class ParkingSpotController extends Controller
{
    public function create()
    {
        $capacity = config('categories.parking_spot_capacity');
        return view('parking_spot.create', compact('capacity'));
    }

    public function confirm(ParkingSpotRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['address'] = $validatedData['address1'] . $validatedData['address2'];

        $capacity = config('categories.parking_spot_capacity');

        return view('parking_spot.confirm', compact('validatedData', 'capacity'));
    }

    public function store(ParkingSpotRequest $request)
    {
        dump($request->all());
    }

    public function createBack(Request $request)
    {
        return redirect()->route('parking_spot.create')->withInput();
    }
}
