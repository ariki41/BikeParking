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
        $this->dispatch('displayMarkers', ['spots' => $this->spots]);

        return view('livewire.parking-spots', ['spots' => $this->spots]);
    }

    public function updateBounds($bounds)
    {
        $this->spots = ParkingSpot::whereBetween('latitude', [$bounds['south'], $bounds['north']])
            ->whereBetween('longitude', [$bounds['west'], $bounds['east']])
            ->limit(10)
            ->get();
    }
}
