<?php
use App\Modules\Invoice\Controllers\InvoiceController;
use App\Modules\Invoice\Controllers\InvoiceLayoutController;

Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
Route::get('invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');

#Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');

// Mahnung (Reminder) Routes
Route::post('invoices/{invoice}/send-reminder', [InvoiceController::class, 'sendReminder'])->name('invoices.send-reminder');
Route::get('invoices/{invoice}/reminder-history', [InvoiceController::class, 'reminderHistory'])->name('invoices.reminder-history');

// Audit Log Route
Route::get('invoices/{invoice}/audit-log', [InvoiceController::class, 'auditLog'])->name('invoices.audit-log');

// E-Rechnung Routes
Route::get('invoices/{invoice}/xrechnung', [InvoiceController::class, 'downloadXRechnung'])->name('invoices.xrechnung');
Route::get('invoices/{invoice}/zugferd', [InvoiceController::class, 'downloadZugferd'])->name('invoices.zugferd');

// Correction Routes
Route::post('invoices/{invoice}/correction', [InvoiceController::class, 'createCorrection'])->name('invoices.create-correction');

// Invoice Layout Routes
Route::prefix('invoice-layouts')->name('invoice-layouts.')->group(function () {
    Route::get('/', [InvoiceLayoutController::class, 'index'])->name('index');
    Route::post('/', [InvoiceLayoutController::class, 'store'])->name('store');
    Route::get('/{invoiceLayout}/preview', [InvoiceLayoutController::class, 'preview'])->name('preview');
    Route::post('/preview-live', [InvoiceLayoutController::class, 'previewLive'])->name('preview-live');
    Route::post('/preview-live-pdf', [InvoiceLayoutController::class, 'previewLivePdf'])->name('preview-live-pdf');
    Route::put('/{invoiceLayout}', [InvoiceLayoutController::class, 'update'])->name('update');
    Route::delete('/{invoiceLayout}', [InvoiceLayoutController::class, 'destroy'])->name('destroy');
    Route::post('/{invoiceLayout}/set-default', [InvoiceLayoutController::class, 'setDefault'])->name('set-default');
    Route::post('/{invoiceLayout}/duplicate', [InvoiceLayoutController::class, 'duplicate'])->name('duplicate');
});

// Redirect settings route to invoice-layouts
Route::get('/settings/invoice-layouts', function () {
    return redirect()->route('invoice-layouts.index');
})->name('settings.invoice-layouts');
