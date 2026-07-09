<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParkingSpotRates extends Model
{
    protected $fillable = [
        'parking_spot_id',
        'day_type',
        'start_time',
        'end_time',
        'rate',
        'max_rate',
    ];

    public function parkingSpot(): BelongsTo
    {
        return $this->belongsTo(ParkingSpot::class);
    }
}
