<?php

namespace App\Livewire;

use App\Domain\ParkingSpots\EngineDisplacementClass;
use App\Models\ParkingSpot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Livewire\Attributes\Url;
use Livewire\Component;

class ParkingSpots extends Component
{
    public $spots = [];

    public ?string $keyword = null;

    public $latitude;

    public $longitude;

    public ?string $engineDisplacement = null;

    #[Url(as: 'capacity', history: true, except: '')]
    public $capacityQuery = '';

    #[Url(as: 'open_24_hours', history: true, except: '')]
    public $open24HoursQuery = '';

    #[Url(as: 'has_free_time', history: true, except: '')]
    public $hasFreeTimeQuery = '';

    #[Url(as: 'max_rate', history: true, except: '')]
    public $maxRateQuery = '';

    public array $filters = [];

    public array $capacityDraft = [];

    public bool $open24HoursDraft = false;

    public bool $hasFreeTimeDraft = false;

    public $maxRateDraft = null;

    public array $bounds = [];

    public bool $hasSearched = false;

    protected $listeners = ['updateBounds'];

    public function mount(
        ?string $keyword = null,
        $latitude = null,
        $longitude = null,
        ?string $engineDisplacement = null,
    ): void {
        $this->keyword = $keyword;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->engineDisplacement = EngineDisplacementClass::tryFrom((string) $engineDisplacement)?->value;

        $this->syncAppliedFiltersFromQuery();
    }

    public function render()
    {
        $this->dispatch('displayMarkers', spots: $this->spots);

        return view('livewire.parking-spots', [
            'activeFilterLabels' => $this->activeFilterLabels(),
            'displacementClasses' => EngineDisplacementClass::cases(),
            'spots' => $this->spots,
        ]);
    }

    public function updateBounds($bounds): void
    {
        if (! is_array($bounds) || collect(['south', 'north', 'west', 'east'])->contains(
            fn (string $key): bool => ! isset($bounds[$key]) || ! is_numeric($bounds[$key]),
        )) {
            return;
        }

        $this->bounds = collect(['south', 'north', 'west', 'east'])
            ->mapWithKeys(fn (string $key): array => [$key => (float) $bounds[$key]])
            ->all();

        $this->refreshSpots();
    }

    public function applyFilters(): void
    {
        $this->validate(
            ['maxRateDraft' => ['nullable', 'integer', 'min:1']],
            [
                'maxRateDraft.integer' => '最大料金上限は整数で入力してください。',
                'maxRateDraft.min' => '最大料金上限は1円以上で入力してください。',
            ],
        );

        $this->filters = $this->normalizeFilters([
            'capacity' => $this->capacityDraft,
            'open_24_hours' => $this->open24HoursDraft,
            'has_free_time' => $this->hasFreeTimeDraft,
            'max_rate' => $this->maxRateDraft,
        ]);

        $this->syncQueryFromAppliedFilters();
        $this->syncDraftsFromFilters($this->filters);
        $this->refreshSpots();
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->capacityDraft = [];
        $this->open24HoursDraft = false;
        $this->hasFreeTimeDraft = false;
        $this->maxRateDraft = null;
        $this->capacityQuery = '';
        $this->open24HoursQuery = '';
        $this->hasFreeTimeQuery = '';
        $this->maxRateQuery = '';
        $this->resetValidation();

        $this->refreshSpots();
    }

    public function updatedCapacityQuery(): void
    {
        $this->syncAppliedFiltersFromQuery();
    }

    public function updatedOpen24HoursQuery(): void
    {
        $this->syncAppliedFiltersFromQuery();
    }

    public function updatedHasFreeTimeQuery(): void
    {
        $this->syncAppliedFiltersFromQuery();
    }

    public function updatedMaxRateQuery(): void
    {
        $this->syncAppliedFiltersFromQuery();
    }

    private function refreshSpots(): void
    {
        if ($this->bounds === []) {
            return;
        }

        $capacityFilters = $this->filters['capacity'] ?? [];
        $open24Hours = $this->filters['open_24_hours'] ?? false;
        $hasFreeTime = $this->filters['has_free_time'] ?? false;
        $maxRate = $this->filters['max_rate'] ?? null;

        $query = ParkingSpot::query()
            ->withRateSummary()
            ->withCount(['favorites', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->whereBetween('latitude', [$this->bounds['south'], $this->bounds['north']])
            ->whereBetween('longitude', [$this->bounds['west'], $this->bounds['east']])
            ->supportsEngineDisplacement(
                EngineDisplacementClass::tryFrom((string) $this->engineDisplacement),
            )
            ->when($capacityFilters !== [], fn (Builder $query) => $query->whereIn('capacity', $capacityFilters))
            ->when($open24Hours, fn (Builder $query) => $query
                ->where('opening_time', '00:00:00')
                ->where('closing_time', '00:00:00'))
            ->when($hasFreeTime || $maxRate !== null, function (Builder $query) use ($hasFreeTime, $maxRate): void {
                $query->whereHas('rates', function (Builder $rateQuery) use ($hasFreeTime, $maxRate): void {
                    if ($hasFreeTime) {
                        $rateQuery->where('free_minutes', '>', 0);
                    }

                    if ($maxRate !== null) {
                        $rateQuery
                            ->whereNotNull('max_rate')
                            ->where('max_rate', '<=', $maxRate);
                    }
                });
            });

        if ($user = auth()->user()) {
            $query->withExists([
                'favorites as is_favorited' => fn ($favoriteQuery) => $favoriteQuery->where('user_id', $user->id),
            ]);
        }

        $this->spots = $query->limit(50)->get();
        $this->hasSearched = true;
    }

    private function syncDraftsFromFilters(array $filters): void
    {
        $this->capacityDraft = $this->normalizedCapacities($filters['capacity'] ?? []);
        $this->open24HoursDraft = $this->filterIsEnabled($filters['open_24_hours'] ?? false);
        $this->hasFreeTimeDraft = $this->filterIsEnabled($filters['has_free_time'] ?? false);

        $maxRate = $filters['max_rate'] ?? null;
        $this->maxRateDraft = is_scalar($maxRate) ? $maxRate : null;
    }

    private function syncAppliedFiltersFromQuery(): void
    {
        $rawFilters = [
            'capacity' => $this->capacityQuery,
            'open_24_hours' => $this->open24HoursQuery,
            'has_free_time' => $this->hasFreeTimeQuery,
            'max_rate' => $this->maxRateQuery,
        ];

        $this->filters = $this->normalizeFilters($rawFilters);
        $this->syncDraftsFromFilters($rawFilters);
        $this->syncQueryFromAppliedFilters(preserveInvalidMaxRate: true);
        $this->resetValidation();

        if (! $this->maxRateIsValid($this->maxRateDraft)) {
            $this->addError('maxRateDraft', '最大料金上限は1円以上の整数で入力してください。');
        }

        $this->refreshSpots();
    }

    private function syncQueryFromAppliedFilters(bool $preserveInvalidMaxRate = false): void
    {
        $this->capacityQuery = implode(',', $this->filters['capacity'] ?? []);
        $this->open24HoursQuery = ($this->filters['open_24_hours'] ?? false) ? '1' : '';
        $this->hasFreeTimeQuery = ($this->filters['has_free_time'] ?? false) ? '1' : '';

        if (! $preserveInvalidMaxRate || $this->maxRateIsValid($this->maxRateQuery)) {
            $this->maxRateQuery = isset($this->filters['max_rate'])
                ? (string) $this->filters['max_rate']
                : '';
        }
    }

    private function normalizeFilters(array $filters): array
    {
        $normalized = [];
        $capacities = $this->normalizedCapacities($filters['capacity'] ?? []);

        if ($capacities !== []) {
            $normalized['capacity'] = $capacities;
        }

        if ($this->filterIsEnabled($filters['open_24_hours'] ?? false)) {
            $normalized['open_24_hours'] = true;
        }

        if ($this->filterIsEnabled($filters['has_free_time'] ?? false)) {
            $normalized['has_free_time'] = true;
        }

        $maxRate = $filters['max_rate'] ?? null;

        if ($this->maxRateIsValid($maxRate) && filled($maxRate)) {
            $normalized['max_rate'] = (int) $maxRate;
        }

        return $normalized;
    }

    private function normalizedCapacities($capacities): array
    {
        $allowed = array_map('intval', array_keys(config('categories.parking_spot_capacity')));
        $values = is_string($capacities) ? explode(',', $capacities) : Arr::wrap($capacities);

        $normalized = collect($values)
            ->filter(fn ($capacity): bool => is_scalar($capacity) && in_array((int) $capacity, $allowed, true))
            ->map(fn ($capacity): int => (int) $capacity)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $normalized;
    }

    private function filterIsEnabled($value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on'], true);
    }

    private function maxRateIsValid($value): bool
    {
        if (blank($value)) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false && (int) $value >= 1;
    }

    private function activeFilterLabels(): array
    {
        $labels = collect($this->filters['capacity'] ?? [])
            ->map(fn (int $capacity): string => '収容台数: '.config("categories.parking_spot_capacity.{$capacity}"))
            ->all();

        if ($this->filters['open_24_hours'] ?? false) {
            $labels[] = '24時間営業';
        }

        if ($this->filters['has_free_time'] ?? false) {
            $labels[] = '無料時間あり';
        }

        if (isset($this->filters['max_rate'])) {
            $labels[] = '最大料金: '.number_format($this->filters['max_rate']).'円以下';
        }

        return $labels;
    }
}
