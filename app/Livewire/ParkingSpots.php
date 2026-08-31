<?php

namespace App\Livewire;

use App\Models\ParkingSpot;
use Livewire\Component;

class ParkingSpots extends Component
{
    public $spots = [];

    protected $listeners = ['updateBounds'];

    public function render()
    {
        $this->dispatch('displayMarkers', spots: $this->spots);

        return view('livewire.parking-spots', ['spots' => $this->spots]);
    }

    public function updateBounds($bounds)
    {
        $query = ParkingSpot::query()
            ->withRateSummary()
            ->withCount(['favorites', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->whereBetween('latitude', [$bounds['south'], $bounds['north']])
            ->whereBetween('longitude', [$bounds['west'], $bounds['east']])
            ->limit(50);

        if ($user = auth()->user()) {
            $query->withExists([
                'favorites as is_favorited' => fn ($favoriteQuery) => $favoriteQuery->where('user_id', $user->id),
            ]);
        }

        $this->spots = $query->get();
    }
}
