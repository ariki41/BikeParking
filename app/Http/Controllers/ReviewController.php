<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\ParkingSpot;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(ParkingSpot $parkingSpot): View
    {
        $parkingSpot
            ->loadCount('reviews')
            ->loadAvg('reviews', 'rating');

        $reviews = $parkingSpot->reviews()
            ->with('user')
            ->paginate(10);

        return view('reviews.index', compact('parkingSpot', 'reviews'));
    }

    public function store(ReviewRequest $request, ParkingSpot $parkingSpot): RedirectResponse
    {
        $review = Review::where('user_id', $request->user()->id)
            ->where('parking_spot_id', $parkingSpot->id)
            ->first();

        if ($review) {
            Gate::authorize('update', $review);
            $message = '評価を更新しました。';
        } else {
            Gate::authorize('create', [Review::class, $parkingSpot]);
            $review = new Review;
            $review->user()->associate($request->user());
            $review->parkingSpot()->associate($parkingSpot);
            $message = '評価を投稿しました。';
        }

        $review->fill($request->validated())->save();

        return redirect()
            ->route('parking_spot.show', $parkingSpot)
            ->with('review_success', $message);
    }
}
