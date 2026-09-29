<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

// Export routes - available to all authenticated users, gated per module so a
// company without e.g. the customers module cannot pull a customer export.
Route::middleware('auth')->group(function () {
    Route::get('export/customers', [ExportController::class, 'exportCustomers'])
        ->middleware('company.module:customers')->name('export.customers');
    Route::get('export/products', [ExportController::class, 'exportProducts'])
        ->middleware('company.module:products')->name('export.products');
    Route::get('export/invoices', [ExportController::class, 'exportInvoices'])
        ->middleware('company.module:invoices')->name('export.invoices');
    Route::get('export/offers', [ExportController::class, 'exportOffers'])
        ->middleware('company.module:offers')->name('export.offers');
});

// Import routes - admin only (requires manage_settings permission)
Route::middleware(['auth', 'can:manage_settings'])->group(function () {
    Route::post('import/customers', [ImportController::class, 'importCustomers'])
        ->middleware('company.module:customers')->name('import.customers');
    Route::post('import/products', [ImportController::class, 'importProducts'])
        ->middleware('company.module:products')->name('import.products');
    Route::post('import/invoices', [ImportController::class, 'importInvoices'])
        ->middleware('company.module:invoices')->name('import.invoices');
});
