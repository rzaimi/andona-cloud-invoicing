<?php

use App\Modules\Reports\Controllers\ReportsController;

Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
Route::get('/reports/revenue', [ReportsController::class, 'revenue'])->name('reports.revenue');
Route::get('/reports/customers', [ReportsController::class, 'customers'])->name('reports.customers');
Route::get('/reports/tax', [ReportsController::class, 'tax'])->name('reports.tax');

