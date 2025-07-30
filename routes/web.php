<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ParkingSpotController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/search', [SearchController::class, 'index'])->name('search');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/parking-spot/create', [ParkingSpotController::class, 'create'])->name('parking_spot.create');
    Route::post('/parking-spot/store', [ParkingSpotController::class, 'store'])->name('parking_spot.store');
    Route::post('/parking-spot/confirm', [ParkingSpotController::class, 'confirm'])->name('parking_spot.confirm');
    Route::post('/parking-spot/create', [ParkingSpotController::class, 'createBack'])->name('parking_spot.create_back');
});

require __DIR__.'/auth.php';
