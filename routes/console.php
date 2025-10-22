<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the news fetch command to run daily at 6:00 AM
Schedule::command('news:fetch')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->timezone('UTC')
    ->appendOutputTo(storage_path('logs/scheduler.log'));
