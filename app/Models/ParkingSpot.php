<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkingSpot extends Model
{
    use HasFactory;

    public function rates(): HasMany
    {
        return $this->hasMany(ParkingSpotRates::class);
    }
}
