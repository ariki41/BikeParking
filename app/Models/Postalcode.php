<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Postalcode extends Model
{
    protected $fillable = ['postalcode', 'city_id', 'name', 'name_kana'];

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
