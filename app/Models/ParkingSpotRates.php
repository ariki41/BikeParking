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

    public function getTimeRangeLabelAttribute(): string
    {
        return self::formatTimeRange($this->start_time, $this->end_time);
    }

    public static function formatTimeRange(?string $startTime, ?string $endTime): string
    {
        $startLabel = self::formatTimeLabel($startTime);
        $endLabel = self::formatTimeLabel($endTime, self::isOvernight($startTime, $endTime));

        return "{$startLabel} ～ {$endLabel}";
    }

    public function parkingSpot(): BelongsTo
    {
        return $this->belongsTo(ParkingSpot::class);
    }

    private static function isOvernight(?string $startTime, ?string $endTime): bool
    {
        if ($startTime === null || $endTime === null) {
            return false;
        }

        return self::normalizeTime($startTime) > self::normalizeTime($endTime);
    }

    private static function formatTimeLabel(?string $time, bool $isNextDay = false): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        $label = self::normalizeTime($time);

        if (! $isNextDay && $label === '00:00') {
            return '24:00';
        }

        return $isNextDay ? "翌{$label}" : $label;
    }

    private static function normalizeTime(string $time): string
    {
        return date('H:i', strtotime($time));
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
