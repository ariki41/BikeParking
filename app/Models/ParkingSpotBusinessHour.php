<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParkingSpotBusinessHour extends Model
{
    use HasFactory;

    protected $fillable = ['day_type', 'is_closed', 'opening_time', 'closing_time'];

    protected function casts(): array
    {
        return ['is_closed' => 'boolean'];
    }

    public function parkingSpot(): BelongsTo
    {
        return $this->belongsTo(ParkingSpot::class);
    }

    public function formattedHours(): string
    {
        $opening = substr((string) $this->opening_time, 0, 5);
        $closing = substr((string) $this->closing_time, 0, 5);

        if ($this->is_closed) {
            return $opening === '00:00' && $closing === '00:00'
                ? '終日休業'
                : $opening.' ～ '.($closing === '00:00' ? '翌0:00' : $closing).' 休業';
        }

        return $opening === '00:00' && $closing === '00:00'
            ? '24時間営業'
            : $opening.' ～ '.($closing === '00:00' ? '翌0:00' : $closing);
    }
}
