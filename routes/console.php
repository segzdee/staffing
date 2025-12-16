<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| AGY-REG-005: Agency Compliance Monitoring Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Daily compliance monitoring at 6:00 AM
Schedule::command('agency:monitor-compliance')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/compliance-monitoring.log'))
    ->description('Daily agency compliance monitoring');

// Process go-live requests every 4 hours
Schedule::command('agency:process-go-live --auto-approve')
    ->everyFourHours()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/go-live-processing.log'))
    ->description('Process pending agency go-live requests');

$user=\Auth::user();