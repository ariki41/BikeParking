<?php

namespace App\Livewire;

use App\Domain\ParkingSpots\EngineDisplacementClass;
use App\Domain\ParkingSpots\ParkingSpotSearchFilters;
use App\Domain\ParkingSpots\ParkingSpotSearchQuery;
use Livewire\Attributes\Url;
use Livewire\Component;

class ParkingSpots extends Component
{
    private const RESULTS_PER_PAGE = 50;

    public $spots = [];

    public int $totalSpots = 0;

    public int $lastPage = 1;

    #[Url(as: 'page', history: true, except: 1)]
    public int $page = 1;

    public ?string $keyword = null;

    #[Url(as: 'lat', keep: true)]
    public $latitude;

    #[Url(as: 'lon', keep: true)]
    public $longitude;

    public array $engineDisplacements = [];

    #[Url(as: 'engine_displacement', history: true, except: '')]
    public $engineDisplacementQuery = '';

    #[Url(as: 'zoom', keep: true)]
    public $zoom = 15;

    #[Url(as: 'capacity', history: true, except: '')]
    public $capacityQuery = '';

    #[Url(as: 'open_24_hours', history: true, except: '')]
    public $open24HoursQuery = '';

    #[Url(as: 'has_free_time', history: true, except: '')]
    public $hasFreeTimeQuery = '';

    #[Url(as: 'max_rate', history: true, except: '')]
    public $maxRateQuery = '';

    #[Url(as: 'exclude_closed', history: true, except: '')]
    public $excludeClosedQuery = '';

    public array $filters = [];

    public array $capacityDraft = [];

    public bool $open24HoursDraft = false;

    public bool $hasFreeTimeDraft = false;

    public $maxRateDraft = null;

    public bool $excludeClosedDraft = false;

    public array $engineDisplacementDraft = [];

    public int $filterFormVersion = 0;

    public array $bounds = [];

    public bool $hasSearched = false;

    protected $listeners = ['updateBounds'];

    public function mount(
        ?string $keyword = null,
        $latitude = null,
        $longitude = null,
        $engineDisplacement = null,
        $zoom = null,
    ): void {
        $this->keyword = $keyword;
        $this->hasSearched = filled($keyword);
        $this->latitude = $latitude ?? $this->latitude;
        $this->longitude = $longitude ?? $this->longitude;
        $this->syncEngineDisplacements($engineDisplacement ?? $this->engineDisplacementQuery);
        $this->zoom = $this->normalizeZoom($zoom ?? $this->zoom);

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

    public function updateBounds($bounds, $zoom = null, $center = null, bool $isInitial = false): void
    {
        if ($zoom !== null) {
            $this->zoom = $this->normalizeZoom($zoom);
        }

        if (! $isInitial) {
            $this->syncCenter($center);
        }

        if (! is_array($bounds) || collect(['south', 'north', 'west', 'east'])->contains(
            fn (string $key): bool => ! isset($bounds[$key]) || ! is_numeric($bounds[$key]),
        )) {
            return;
        }

        $this->bounds = collect(['south', 'north', 'west', 'east'])
            ->mapWithKeys(fn (string $key): array => [$key => (float) $bounds[$key]])
            ->all();

        if (! $isInitial) {
            $this->resetSearchPage();
        }
        $this->refreshSpots();
    }

    private function syncCenter(mixed $center): void
    {
        if (! is_array($center)
            || ! is_numeric($center['latitude'] ?? null)
            || ! is_numeric($center['longitude'] ?? null)) {
            return;
        }

        $latitude = (float) $center['latitude'];
        $longitude = (float) $center['longitude'];

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return;
        }

        $this->latitude = round($latitude, 6);
        $this->longitude = round($longitude, 6);
    }

    private function normalizeZoom(mixed $zoom): int
    {
        $normalizedZoom = filter_var($zoom, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => config('parking_spot.search_map.min_zoom'),
                'max_range' => config('parking_spot.search_map.max_zoom'),
            ],
        ]);

        return $normalizedZoom === false
            ? config('parking_spot.search_map.default_zoom')
            : $normalizedZoom;
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

        $this->filters = ParkingSpotSearchFilters::from([
            'capacity' => $this->capacityDraft,
            'open_24_hours' => $this->open24HoursDraft,
            'has_free_time' => $this->hasFreeTimeDraft,
            'max_rate' => $this->maxRateDraft,
            'exclude_closed' => $this->excludeClosedDraft,
        ])->applied();
        $this->syncEngineDisplacements($this->engineDisplacementDraft);

        $this->syncQueryFromAppliedFilters();
        $this->syncDraftsFromFilters($this->filters);
        $this->hasSearched = true;
        $this->resetSearchPage();
        $this->refreshSpots();
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->capacityDraft = [];
        $this->open24HoursDraft = false;
        $this->hasFreeTimeDraft = false;
        $this->maxRateDraft = null;
        $this->excludeClosedDraft = false;
        $this->engineDisplacements = [];
        $this->engineDisplacementQuery = '';
        $this->engineDisplacementDraft = [];
        $this->capacityQuery = '';
        $this->open24HoursQuery = '';
        $this->hasFreeTimeQuery = '';
        $this->maxRateQuery = '';
        $this->excludeClosedQuery = '';
        $this->filterFormVersion++;
        $this->resetValidation();

        $this->resetSearchPage();
        $this->refreshSpots();
    }

    public function goToPage(int $page): void
    {
        $this->page = max(1, min($page, $this->lastPage));
        $this->refreshSpots();
    }

    public function updatedEngineDisplacementQuery(): void
    {
        $this->syncEngineDisplacements($this->engineDisplacementQuery);
        $this->resetSearchPage();
        $this->refreshSpots();
    }

    public function updatedCapacityQuery(): void
    {
        $this->resetSearchPage();
        $this->syncAppliedFiltersFromQuery();
    }

    public function updatedOpen24HoursQuery(): void
    {
        $this->resetSearchPage();
        $this->syncAppliedFiltersFromQuery();
    }

    public function updatedHasFreeTimeQuery(): void
    {
        $this->resetSearchPage();
        $this->syncAppliedFiltersFromQuery();
    }

    public function updatedMaxRateQuery(): void
    {
        $this->resetSearchPage();
        $this->syncAppliedFiltersFromQuery();
    }

    public function updatedExcludeClosedQuery(): void
    {
        $this->resetSearchPage();
        $this->syncAppliedFiltersFromQuery();
    }

    private function syncEngineDisplacements(mixed $engineDisplacements): void
    {
        $this->engineDisplacements = ParkingSpotSearchFilters::from([], $engineDisplacements)->engineDisplacements;
        $this->engineDisplacementQuery = implode(',', $this->engineDisplacements);
        $this->engineDisplacementDraft = $this->engineDisplacements;
    }

    private function refreshSpots(): void
    {
        if ($this->bounds === []) {
            return;
        }

        $results = app(ParkingSpotSearchQuery::class)->paginate(
            $this->bounds,
            ParkingSpotSearchFilters::from($this->filters, $this->engineDisplacements),
            auth()->user(),
            $this->page,
            self::RESULTS_PER_PAGE,
        );

        $this->totalSpots = $results['total'];
        $this->lastPage = $results['lastPage'];
        $this->page = max(1, min($this->page, $this->lastPage));
        $this->spots = $results['spots']->all();
    }

    private function resetSearchPage(): void
    {
        $this->page = 1;
    }

    private function syncDraftsFromFilters(array $filters): void
    {
        $normalized = ParkingSpotSearchFilters::from($filters);
        $this->capacityDraft = $normalized->capacities;
        $this->open24HoursDraft = $normalized->open24Hours;
        $this->hasFreeTimeDraft = $normalized->hasFreeTime;
        $this->excludeClosedDraft = $normalized->excludeClosed;

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
            'exclude_closed' => $this->excludeClosedQuery,
        ];

        $this->filters = ParkingSpotSearchFilters::from($rawFilters)->applied();
        $this->syncDraftsFromFilters($rawFilters);
        // 無効なURL値を消すと修正すべき入力が見えなくなるため、エラー表示中はクエリに残す。
        $this->syncQueryFromAppliedFilters(preserveInvalidMaxRate: true);
        $this->resetValidation();

        if (! ParkingSpotSearchFilters::maxRateIsValid($this->maxRateDraft)) {
            $this->addError('maxRateDraft', '最大料金上限は1円以上の整数で入力してください。');
        }

        $this->refreshSpots();
    }

    private function syncQueryFromAppliedFilters(bool $preserveInvalidMaxRate = false): void
    {
        $parameters = ParkingSpotSearchFilters::from($this->filters)->queryParameters();
        $this->capacityQuery = $parameters['capacity'];
        $this->open24HoursQuery = $parameters['open_24_hours'];
        $this->hasFreeTimeQuery = $parameters['has_free_time'];
        $this->excludeClosedQuery = $parameters['exclude_closed'];

        if (! $preserveInvalidMaxRate || ParkingSpotSearchFilters::maxRateIsValid($this->maxRateQuery)) {
            $this->maxRateQuery = $parameters['max_rate'];
        }
    }

    private function activeFilterLabels(): array
    {
        return ParkingSpotSearchFilters::from($this->filters, $this->engineDisplacements)->labels();
    }
}
