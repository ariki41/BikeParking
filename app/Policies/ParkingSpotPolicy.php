<?php

namespace App\Policies;

use App\Models\ParkingSpot;
use App\Models\User;

class ParkingSpotPolicy
{
    /**
     * 駐輪場は共同編集とし、ログイン済みユーザーであれば更新できる。
     */
    public function update(User $user, ParkingSpot $parkingSpot): bool
    {
        return $user->exists;
    }
}
