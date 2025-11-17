<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

// Export routes - available to all authenticated users
Route::middleware('auth')->group(function () {
    Route::get('export/customers', [ExportController::class, 'exportCustomers'])->name('export.customers');
    Route::get('export/products', [ExportController::class, 'exportProducts'])->name('export.products');
    Route::get('export/invoices', [ExportController::class, 'exportInvoices'])->name('export.invoices');
    Route::get('export/offers', [ExportController::class, 'exportOffers'])->name('export.offers');
});

// Import routes - admin only (requires manage_settings permission)
Route::middleware(['auth', 'can:manage_settings'])->group(function () {
    Route::post('import/customers', [ImportController::class, 'importCustomers'])->name('import.customers');
    Route::post('import/products', [ImportController::class, 'importProducts'])->name('import.products');
    Route::post('import/invoices', [ImportController::class, 'importInvoices'])->name('import.invoices');
});

