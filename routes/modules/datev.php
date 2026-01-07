<?php

use App\Modules\Datev\Controllers\DatevController;

Route::middleware('auth')->group(function () {
    Route::get('/datev', [DatevController::class, 'index'])->name('datev.index');
    Route::post('/datev/export/transactions', [DatevController::class, 'exportTransactions'])->name('datev.export.transactions');
    Route::post('/datev/export/customers', [DatevController::class, 'exportCustomers'])->name('datev.export.customers');
    Route::post('/datev/export/payments', [DatevController::class, 'exportPayments'])->name('datev.export.payments');
    Route::post('/datev/export/expenses', [DatevController::class, 'exportExpenses'])->name('datev.export.expenses');
    Route::post('/datev/export/vat', [DatevController::class, 'exportVat'])->name('datev.export.vat');
});



