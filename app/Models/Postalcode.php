<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Postalcode extends Model
{
    protected $fillable = ['postalcode', 'city_id', 'name', 'name_kana'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function parkingSpots(): HasMany
    {
        return $this->hasMany(ParkingSpot::class);
    }

    public function fullAddress(): string
    {
        return $this->city->prefecture->name.$this->city->name.$this->name;
    }
}
