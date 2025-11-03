<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canLogin' => Route::has('login'),
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
    // Load company settings FIRST (more specific routes first)
    require __DIR__.'/modules/settings.php'; // Company settings - /settings
    // Then load user settings (more specific routes)
    require __DIR__.'/settings.php'; // User profile/password/appearance settings - /settings/profile, etc.
    require __DIR__.'/modules/help.php';
    require __DIR__.'/modules/calendar.php';
    require __DIR__.'/modules/reports.php';
});

require __DIR__.'/auth.php';
