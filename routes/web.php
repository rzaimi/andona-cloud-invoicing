<?php

use App\Http\Controllers\ContactController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Tighten\Ziggy\Ziggy;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canLogin' => Route::has('login'),
    ]);
});

// Contact routes (public) — throttled to 10 requests per minute per IP
Route::post('/contact/demo', [ContactController::class, 'demo'])
    ->middleware('throttle:10,1')
    ->name('contact.demo');

// API endpoint to load Ziggy routes dynamically (hides routes from HTML source)
Route::get('/api/routes', function (Request $request) {
    return response()->json([
        ...(new Ziggy)->toArray(),
        'location' => $request->url(),
    ]);
})->middleware('auth');

Route::middleware(['auth', 'session.timeout', 'employee.portal.only'])->group(function () {
    require __DIR__.'/modules/dashboard.php';
    require __DIR__.'/modules/profile.php';

    Route::middleware('company.module:customers')->group(function () {
        require __DIR__.'/modules/customers.php';
    });
    Route::middleware('company.module:products')->group(function () {
        require __DIR__.'/modules/products.php';
    });
    Route::middleware('company.module:invoices')->group(function () {
        require __DIR__.'/modules/invoices.php';
    });
    Route::middleware('company.module:dunning')->group(function () {
        require __DIR__.'/modules/dunning.php';
    });
    Route::middleware('company.module:offers')->group(function () {
        require __DIR__.'/modules/offers.php';
    });
    Route::middleware('company.module:payments')->group(function () {
        require __DIR__.'/modules/payments.php';
    });
    // Import/export routes carry their own per-route module middleware.
    require __DIR__.'/modules/import-export.php';
    Route::middleware('company.module:documents')->group(function () {
        require __DIR__.'/modules/documents.php';
    });
    Route::middleware('company.module:calendar')->group(function () {
        require __DIR__.'/modules/calendar.php';
    });
    Route::middleware('company.module:reports')->group(function () {
        require __DIR__.'/modules/reports.php';
    });
    Route::middleware('company.module:datev')->group(function () {
        require __DIR__.'/modules/datev.php';
    });
    Route::middleware('company.module:expenses')->group(function () {
        require __DIR__.'/modules/expenses.php';
    });

    require __DIR__.'/modules/admin.php';
    // Load company settings FIRST (more specific routes first)
    require __DIR__.'/modules/settings.php'; // Company settings - /settings
    // Then load user settings (more specific routes)
    require __DIR__.'/settings.php'; // User profile/password/appearance settings - /settings/profile, etc.
    require __DIR__.'/modules/help.php';
    require __DIR__.'/modules/employee.php';
});

require __DIR__.'/auth.php';
