<?php

use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ParkingSpotController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/search', [SearchController::class, 'index'])->name('search');

Route::view('/privacy', 'privacy')->name('privacy');

Route::get('/ads.txt', function () {
    $client = config('advertising.adsense.client');

    abort_unless(
        config('advertising.enabled') && is_string($client) && Str::startsWith($client, 'ca-pub-'),
        404,
    );

    return response(
        'google.com, '.Str::after($client, 'ca-').", DIRECT, f08c47fec0942fa0\n",
        200,
        ['Content-Type' => 'text/plain; charset=UTF-8'],
    );
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/parking-spot/{parkingSpot}/favorite', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('/parking-spot/{parkingSpot}/favorite', [FavoriteController::class, 'destroy'])->name('favorites.destroy');

    Route::post('/parking-spot/{parkingSpot}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/parking-spots/create', [ParkingSpotController::class, 'create'])->name('parking_spot.create');
    Route::post('/parking-spots/confirm', [ParkingSpotController::class, 'confirm'])->name('parking_spot.confirm');
    Route::post('/parking-spots', [ParkingSpotController::class, 'store'])->block()->name('parking_spot.store');
    Route::get('/parking-spots/{parkingSpot}/edit', [ParkingSpotController::class, 'edit'])->name('parking_spot.edit');
    Route::match(['put', 'patch'], '/parking-spots/{parkingSpot}', [ParkingSpotController::class, 'update'])->block()->name('parking_spot.update');
});

Route::get('/parking-spots/{parkingSpot}/reviews', [ReviewController::class, 'index'])->name('reviews.index');
Route::get('/parking-spots/{parkingSpot}', [ParkingSpotController::class, 'show'])->name('parking_spot.show');

require __DIR__.'/auth.php';
