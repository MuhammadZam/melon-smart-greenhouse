<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Required for max runtime and stale-data stops even when no telemetry arrives.
\Illuminate\Support\Facades\Schedule::command('pump:evaluate')->everyFiveSeconds()->withoutOverlapping();
