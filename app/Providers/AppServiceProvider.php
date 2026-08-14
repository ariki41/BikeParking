<?php

namespace App\Providers;

use App\Models\ParkingSpot;
use App\Models\Review;
use App\Policies\ParkingSpotPolicy;
use App\Policies\ReviewPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ParkingSpot::class, ParkingSpotPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
    }
}
