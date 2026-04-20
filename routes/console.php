<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generate recurring invoices BEFORE reminders so a freshly-generated
// invoice that's already overdue can pick up its first reminder the same day.
Schedule::command('invoices:recurring-generate')
    ->dailyAt('08:00')
    ->name('Recurring Invoices')
    ->withoutOverlapping()
    ->emailOutputOnFailure(env('ADMIN_EMAIL', 'admin@example.com'))
    ->onFailure(function () {
        \Log::error('Recurring invoice generation failed');
    });

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
