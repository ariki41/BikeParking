<?php

namespace App\Policies;

use App\Models\ParkingSpot;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function create(User $user, ParkingSpot $parkingSpot): bool
    {
        return $user->exists
            && ! $parkingSpot->reviews()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }
}
