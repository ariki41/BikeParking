<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParkingSpotUpdateHistory extends Model
{
    protected $fillable = [
        'parking_spot_id',
        'user_id',
        'changes',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function parkingSpot(): BelongsTo
    {
        return $this->belongsTo(ParkingSpot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getChangeSummaryAttribute(): string
    {
        $labels = [
            'name' => '駐輪場名',
            'postalcode_id' => '郵便番号',
            'address' => '住所',
            'longitude' => '経度',
            'latitude' => '緯度',
            'opening_time' => '開場時間',
            'closing_time' => '閉場時間',
            'capacity' => '収容台数',
            'image_path' => '画像',
            'images' => '画像',
            'rates' => '料金',
        ];

        return collect(array_keys($this->getAttribute('changes') ?? []))
            ->map(fn (string $field) => $labels[$field] ?? $field)
            ->implode('、') ?: '変更なし';
    }
}
