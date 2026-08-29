<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('parking-spots:prune-temporary-images')->hourly();
Schedule::command('postal-codes:sync')
    ->monthlyOn(2, '03:00')
    ->withoutOverlapping();
