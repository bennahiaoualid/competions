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

// 🆕 Payment System Scheduled Commands

// Auto-expire coin offers daily at 1 AM
Schedule::call(function () {
    app(\App\Console\Commands::class)->expireCoinOffers();
})->dailyAt('01:00');

// Clean up expired payment data and proof images daily at 2 AM
Schedule::call(function () {
    app(\App\Console\Commands::class)->cleanupPaymentData();
})->dailyAt('02:00');

// Auto-reject expired payment reviews daily at 3 AM
Schedule::call(function () {
    app(\App\Console\Commands::class)->autoRejectExpiredPaymentReviews();
})->dailyAt('03:00');

// Archive old payment audit logs daily at 4 AM
Schedule::call(function () {
    app(\App\Console\Commands::class)->archivePaymentAuditLogs();
})->dailyAt('04:00');

// 🆕 LLM System Commands

// Test LLM connection
Artisan::command('llm:test {provider=openai}', function ($provider) {
    $this->call(\App\Console\Commands\TestLLMConnection::class, ['provider' => $provider]);
})->purpose('Test LLM connection for a specific provider');

// Check LLM health status
Artisan::command('llm:health {provider=openai}', function ($provider) {
    app(\App\Console\Commands::class)->checkLLMHealth($provider);
    $this->info("Health check completed for provider: {$provider}");
})->purpose('Check LLM provider health status');

