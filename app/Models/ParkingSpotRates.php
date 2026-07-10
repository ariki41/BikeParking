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
        'unit_minutes',
        'rate',
        'free_minutes',
        'max_rate',
    ];

    protected $casts = [
        'unit_minutes' => 'integer',
        'rate' => 'integer',
        'free_minutes' => 'integer',
        'max_rate' => 'integer',
    ];

    public function getRateLabelAttribute(): string
    {
        $unit = $this->formatMinutes($this->unit_minutes);
        $label = "{$unit} ".number_format($this->rate).'円';

        if ($this->free_minutes > 0) {
            $label = "最初の{$this->formatMinutes($this->free_minutes)}無料 / 以降{$label}";
        }

        if ($this->max_rate !== null) {
            $label .= ' / 最大 '.number_format($this->max_rate).'円';
        } else {
            $label .= ' / 最大料金なし';
        }

        return $label;
    }

    public function parkingSpot(): BelongsTo
    {
        return $this->belongsTo(ParkingSpot::class);
    }

    private function formatMinutes(int $minutes): string
    {
        if ($minutes >= 60 && $minutes % 60 === 0) {
            $hours = $minutes / 60;

            return "{$hours}時間";
        }

        return "{$minutes}分";
    }
}
