<?php

namespace App\Models;

use App\Domain\ParkingSpots\EngineDisplacementClass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read EngineDisplacementClass|null $max_displacement_class
 * @property-write EngineDisplacementClass|string|null $max_displacement_class
 */
class ParkingSpot extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'longitude' => 'float',
            'latitude' => 'float',
            'max_displacement_class' => EngineDisplacementClass::class,
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  list<string>  $engineDisplacements
     */
    public function scopeSupportsEngineDisplacements(
        Builder $query,
        array $engineDisplacements,
    ): Builder {
        $supportedValues = collect($engineDisplacements)
            ->map(fn (string $value): ?EngineDisplacementClass => EngineDisplacementClass::tryFrom($value))
            ->filter()
            ->flatMap(fn (EngineDisplacementClass $class): array => $class->supportedByValues())
            ->unique()
            ->values()
            ->all();

        if ($supportedValues === []) {
            return $query;
        }

        return $query->whereIn('max_displacement_class', $supportedValues);
    }

    public function getCapacityLabelAttribute(): string
    {
        $labels = config('categories.parking_spot_capacity');

        return $labels[$this->capacity] ?? (string) $this->capacity;
    }

    public function getImageUrlAttribute(): string
    {
        return self::imageUrlForPath($this->image_path ?: ($this->image_paths[0] ?? null));
    }

    public function getImagePathsAttribute(): array
    {
        // 暗黙のリレーション取得によるN+1を避け、未ロード時は従来から保持する代表画像を使う。
        $paths = $this->relationLoaded('images')
            ? $this->images->pluck('path')->filter()->values()->all()
            : [];

        if ($paths === [] && filled($this->image_path)) {
            return [$this->image_path];
        }

        return $paths;
    }

    public function getImageUrlsAttribute(): array
    {
        $urls = collect($this->image_paths)
            ->map(fn (string $path) => self::imageUrlForPath($path))
            ->all();

        return $urls ?: [self::imageUrlForPath(null)];
    }

    public static function imageUrlForPath(?string $path): string
    {
        if (blank($path)) {
            return asset('images/noimage.jpg');
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * @return HasMany<ParkingSpotRates, $this>
     */
    public function rates(): HasMany
    {
        return $this->hasMany(ParkingSpotRates::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Postalcode, $this>
     */
    public function postalcode(): BelongsTo
    {
        return $this->belongsTo(Postalcode::class);
    }

    /**
     * @return HasMany<ParkingSpotImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ParkingSpotImage::class)->orderBy('position');
    }

    public function representativeRate(): HasOne
    {
        return $this->hasOne(ParkingSpotRates::class)->oldestOfMany();
    }

    public function scopeWithRateSummary(Builder $query): Builder
    {
        return $query
            ->with('representativeRate')
            ->withCount('rates');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->published()->where($field ?? $this->getRouteKeyName(), $value)->first();
    }

    public function updateHistories(): HasMany
    {
        return $this->hasMany(ParkingSpotUpdateHistory::class)->latest();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)
            ->latest('updated_at')
            ->latest('id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ParkingSpotReport::class);
    }
}
