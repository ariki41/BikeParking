<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ParkingSpot extends Model
{
    use HasFactory;

    public function getCapacityLabelAttribute(): string
    {
        $labels = config('categories.parking_spot_capacity');

        return $labels[$this->capacity] ?? (string) $this->capacity;
    }

    public function getImageUrlAttribute(): string
    {
        return self::imageUrlForPath($this->image_path);
    }

    public static function imageUrlForPath(?string $path): string
    {
        if (blank($path)) {
            return asset('images/noimage.jpg');
        }

        return Storage::disk('public')->url($path);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ParkingSpotRates::class);
    }

    public function updateHistories(): HasMany
    {
        return $this->hasMany(ParkingSpotUpdateHistory::class)->latest();
    }
}
