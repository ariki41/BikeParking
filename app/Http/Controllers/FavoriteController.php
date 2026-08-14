<?php

namespace App\Http\Controllers;

use App\Models\ParkingSpot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $parkingSpots = $request->user()
            ->favoriteParkingSpots()
            ->withRateSummary()
            ->withCount(['favorites', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->orderByPivot('created_at', 'desc')
            ->paginate(12);

        return view('favorites.index', compact('parkingSpots'));
    }

    public function store(Request $request, ParkingSpot $parkingSpot): RedirectResponse
    {
        $request->user()->favorites()->firstOrCreate([
            'parking_spot_id' => $parkingSpot->id,
        ]);

        return back()->with('favorite_success', 'お気に入りに追加しました。');
    }

    public function destroy(Request $request, ParkingSpot $parkingSpot): RedirectResponse
    {
        $request->user()
            ->favorites()
            ->where('parking_spot_id', $parkingSpot->id)
            ->delete();

        return back()->with('favorite_success', 'お気に入りを解除しました。');
    }
}
