<?php

namespace App\Domain\ParkingSpots;

use Illuminate\Support\Arr;

final readonly class ParkingSpotSearchFilters
{
    /**
     * @param  array<int, int>  $capacities
     * @param  array<int, string>  $engineDisplacements
     */
    private function __construct(
        public array $capacities,
        public array $engineDisplacements,
        public bool $open24Hours,
        public bool $hasFreeTime,
        public ?int $maxRate,
        public bool $excludeClosed,
    ) {}

    public static function from(array $filters = [], mixed $engineDisplacements = []): self
    {
        $maxRate = $filters['max_rate'] ?? null;

        return new self(
            capacities: self::normalizeCapacities($filters['capacity'] ?? []),
            engineDisplacements: self::normalizeEngineDisplacements($engineDisplacements),
            open24Hours: self::isEnabled($filters['open_24_hours'] ?? false),
            hasFreeTime: self::isEnabled($filters['has_free_time'] ?? false),
            maxRate: self::maxRateIsValid($maxRate) && filled($maxRate) ? (int) $maxRate : null,
            excludeClosed: self::isEnabled($filters['exclude_closed'] ?? false),
        );
    }

    /** @return array{capacity?: array<int, int>, open_24_hours?: true, has_free_time?: true, max_rate?: int, exclude_closed?: true} */
    public function applied(): array
    {
        return array_filter([
            'capacity' => $this->capacities ?: null,
            'open_24_hours' => $this->open24Hours ?: null,
            'has_free_time' => $this->hasFreeTime ?: null,
            'max_rate' => $this->maxRate,
            'exclude_closed' => $this->excludeClosed ?: null,
        ], fn (mixed $value): bool => $value !== null);
    }

    /** @return array{capacity: string, open_24_hours: string, has_free_time: string, max_rate: string, exclude_closed: string} */
    public function queryParameters(): array
    {
        return [
            'capacity' => implode(',', $this->capacities),
            'open_24_hours' => $this->open24Hours ? '1' : '',
            'has_free_time' => $this->hasFreeTime ? '1' : '',
            'max_rate' => $this->maxRate === null ? '' : (string) $this->maxRate,
            'exclude_closed' => $this->excludeClosed ? '1' : '',
        ];
    }

    /** @return array<int, string> */
    public function labels(): array
    {
        $labels = collect($this->capacities)
            ->map(fn (int $capacity): string => '収容台数: '.config("categories.parking_spot_capacity.{$capacity}"))
            ->all();

        foreach ($this->engineDisplacements as $value) {
            $labels[] = '排気量: '.EngineDisplacementClass::from($value)->searchLabel();
        }

        if ($this->open24Hours) {
            $labels[] = '24時間営業';
        }

        if ($this->hasFreeTime) {
            $labels[] = '無料時間あり';
        }

        if ($this->excludeClosed) {
            $labels[] = '閉鎖済みを除外';
        }

        if ($this->maxRate !== null) {
            $labels[] = '最大料金: '.number_format($this->maxRate).'円以下';
        }

        return $labels;
    }

    public static function maxRateIsValid(mixed $value): bool
    {
        return blank($value)
            || (filter_var($value, FILTER_VALIDATE_INT) !== false && (int) $value >= 1);
    }

    /** @return array<int, int> */
    private static function normalizeCapacities(mixed $capacities): array
    {
        $allowed = array_map('intval', array_keys(config('categories.parking_spot_capacity')));
        $values = is_string($capacities) ? explode(',', $capacities) : Arr::wrap($capacities);

        return collect($values)
            ->filter(fn ($capacity): bool => is_scalar($capacity) && in_array((int) $capacity, $allowed, true))
            ->map(fn ($capacity): int => (int) $capacity)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private static function normalizeEngineDisplacements(mixed $engineDisplacements): array
    {
        $values = is_string($engineDisplacements) ? explode(',', $engineDisplacements) : Arr::wrap($engineDisplacements);
        $selectedValues = collect($values)
            ->filter(fn ($value): bool => is_scalar($value))
            ->map(fn ($value): string => (string) $value)
            ->all();

        return collect(EngineDisplacementClass::values())
            ->filter(fn (string $value): bool => in_array($value, $selectedValues, true))
            ->values()
            ->all();
    }

    private static function isEnabled(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on'], true);
    }
}
