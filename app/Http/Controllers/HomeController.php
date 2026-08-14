<?php

namespace App\Http\Controllers;

use App\Models\ParkingSpot;

class HomeController extends Controller
{
    public function index()
    {
        $parkingSpots = ParkingSpot::withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->latest()
            ->take(3)
            ->get();

        return view('home', compact('parkingSpots'));
    }
}
