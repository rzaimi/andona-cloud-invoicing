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

// Invoice Layout Routes
Route::prefix('invoice-layouts')->name('invoice-layouts.')->group(function () {
    Route::get('/', [InvoiceLayoutController::class, 'index'])->name('index');
    Route::post('/', [InvoiceLayoutController::class, 'store'])->name('store');
    Route::get('/{invoiceLayout}/preview', [InvoiceLayoutController::class, 'preview'])->name('preview');
    Route::put('/{invoiceLayout}', [InvoiceLayoutController::class, 'update'])->name('update');
    Route::delete('/{invoiceLayout}', [InvoiceLayoutController::class, 'destroy'])->name('destroy');
    Route::post('/{invoiceLayout}/set-default', [InvoiceLayoutController::class, 'setDefault'])->name('set-default');
    Route::post('/{invoiceLayout}/duplicate', [InvoiceLayoutController::class, 'duplicate'])->name('duplicate');
});

// Redirect settings route to invoice-layouts
Route::get('/settings/invoice-layouts', function () {
    return redirect()->route('invoice-layouts.index');
})->name('settings.invoice-layouts');
