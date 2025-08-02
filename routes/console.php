<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('app:update-user-leaderboard-results')->everyMinute();

// 🆕 Clean up expired approval requests every 6 hours
Schedule::call(function () {
    app(\App\Console\Commands::class)->cleanupExpiredApprovals();
})->everySixHours();
