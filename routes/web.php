<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth')->group(function () {
    require __DIR__.'/modules/dashboard.php';
    require __DIR__.'/modules/profile.php';
    require __DIR__.'/modules/customers.php';
    require __DIR__.'/modules/products.php';
    require __DIR__.'/modules/invoices.php';
    require __DIR__.'/modules/offers.php';
    require __DIR__.'/modules/admin.php';
    require __DIR__.'/modules/settings.php';
    require __DIR__.'/modules/help.php';
    require __DIR__.'/modules/calendar.php';
    require __DIR__.'/modules/reports.php';
});

require __DIR__.'/auth.php';
