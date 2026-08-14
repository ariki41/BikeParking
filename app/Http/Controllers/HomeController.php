<?php

namespace App\Http\Controllers;

use App\Models\ParkingSpot;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $query = ParkingSpot::withCount(['favorites', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->latest()
            ->take(3);

        if ($user = $request->user()) {
            $query->withExists([
                'favorites as is_favorited' => fn ($favoriteQuery) => $favoriteQuery->where('user_id', $user->id),
            ]);
        }

        $parkingSpots = $query->get();

        return view('home', compact('parkingSpots'));
    }
}
