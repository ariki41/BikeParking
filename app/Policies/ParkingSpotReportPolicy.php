<?php

namespace App\Policies;

use App\Models\User;

class ParkingSpotReportPolicy
{
    public function viewAdmin(User $user): bool
    {
        return $user->is_admin === true;
    }
}
