<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Required for max runtime and stale-data stops even when no telemetry arrives.
Schedule::command('pump:evaluate')->everyFiveSeconds()->withoutOverlapping();

if (config('services.google_sheets.schedule_enabled')) {
    Schedule::command('telemetry:sync-google-sheets --scheduled')->everyMinute()->withoutOverlapping()->runInBackground();
}
