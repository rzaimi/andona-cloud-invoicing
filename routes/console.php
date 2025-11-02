<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily reminders for invoices and offers
Schedule::command('reminders:send')
    ->dailyAt('09:00')
    ->name('Daily Reminders')
    ->emailOutputOnFailure(env('ADMIN_EMAIL', 'admin@example.com'))
    ->onSuccess(function () {
        \Log::info('Daily reminders sent successfully');
    })
    ->onFailure(function () {
        \Log::error('Daily reminders failed');
    });
