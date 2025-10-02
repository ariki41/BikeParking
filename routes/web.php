<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ParkingSpotController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/search', [SearchController::class, 'index'])->name('search');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('parking-spot/detail/{id}', [ParkingSpotController::class, 'show'])->name('parking_spot.show');
    Route::get('/parking-spot/create', [ParkingSpotController::class, 'create'])->name('parking_spot.create');
    Route::post('/parking-spot/store', [ParkingSpotController::class, 'store'])->name('parking_spot.store');
    Route::post('/parking-spot/confirm', [ParkingSpotController::class, 'confirm'])->name('parking_spot.confirm');
    Route::get('/parking-spot/edit/{id}', [ParkingSpotController::class, 'edit'])->name('parking_spot.edit');
    Route::post('/parking-spot/update', [ParkingSpotController::class, 'update'])->name('parking_spot.update');
});

require __DIR__.'/auth.php';
