<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParkingSpotModerationAction extends Model
{
    protected $fillable = ['parking_spot_id', 'parking_spot_update_history_id', 'user_id', 'action', 'details'];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    public function parkingSpot(): BelongsTo
    {
        return $this->belongsTo(ParkingSpot::class);
    }

    public function updateHistory(): BelongsTo
    {
        return $this->belongsTo(ParkingSpotUpdateHistory::class, 'parking_spot_update_history_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
