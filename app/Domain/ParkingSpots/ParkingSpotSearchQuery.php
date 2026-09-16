<?php

namespace App\Domain\ParkingSpots;

use App\Models\ParkingSpot;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ParkingSpotSearchQuery
{
    /**
     * @param  array{south: float, north: float, west: float, east: float}  $bounds
     * @return array{total: int, lastPage: int, spots: Collection<int, ParkingSpot>}
     */
    public function paginate(array $bounds, ParkingSpotSearchFilters $filters, ?User $user, int $page, int $perPage): array
    {
        $query = ParkingSpot::query()
            ->withRateSummary()
            ->withCount(['favorites', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->whereBetween('latitude', [$bounds['south'], $bounds['north']])
            ->whereBetween('longitude', [$bounds['west'], $bounds['east']])
            ->supportsEngineDisplacements($filters->engineDisplacements)
            ->when($filters->capacities !== [], fn (Builder $query) => $query->whereIn('capacity', $filters->capacities))
            ->when($filters->excludeClosed, fn (Builder $query) => $query->published())
            ->when($filters->open24Hours, function (Builder $query): void {
                $query->where(function (Builder $hoursQuery): void {
                    // 移行済みは曜日別レコードを正とし、レコード未作成の旧データだけ従来列へフォールバックする。
                    $hoursQuery->where(function (Builder $legacyQuery): void {
                        $legacyQuery->doesntHave('businessHours')
                            ->where('opening_time', '00:00:00')
                            ->where('closing_time', '00:00:00');
                    })->orWhere(function (Builder $businessHoursQuery): void {
                        $businessHoursQuery->whereHas('businessHours')
                            ->whereDoesntHave('businessHours', fn (Builder $hours) => $hours
                                ->where('is_closed', true)
                                ->orWhere('opening_time', '!=', '00:00:00')
                                ->orWhere('closing_time', '!=', '00:00:00'));
                    });
                });
            })
            ->when($filters->hasFreeTime || $filters->maxRate !== null, function (Builder $query) use ($filters): void {
                $query->whereHas('rates', function (Builder $rateQuery) use ($filters): void {
                    if ($filters->hasFreeTime) {
                        $rateQuery->where('free_minutes', '>', 0);
                    }

                    if ($filters->maxRate !== null) {
                        $rateQuery->whereNotNull('max_rate')->where('max_rate', '<=', $filters->maxRate);
                    }
                });
            });

        if ($user !== null) {
            $query->withExists([
                'favorites as is_favorited' => fn ($favoriteQuery) => $favoriteQuery->where('user_id', $user->id),
            ]);
        }

        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));

        return [
            'total' => $total,
            'lastPage' => $lastPage,
            'spots' => $query->orderBy('id')->forPage($page, $perPage)->get(),
        ];
    }
}
