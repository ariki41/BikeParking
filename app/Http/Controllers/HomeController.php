<?php

namespace App\Http\Controllers;

use App\Models\ParkingSpot;

class HomeController extends Controller
{
    public function index()
    {
        $parkingSpots = ParkingSpot::latest()->take(3)->get();

        return view('home', compact('parkingSpots'));
    }
}
