<?php
use App\Modules\Invoice\Controllers\InvoiceController;
use App\Modules\Invoice\Controllers\InvoiceLayoutController;
use App\Modules\RecurringInvoice\Controllers\RecurringInvoiceController;

Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
Route::get('invoices/board', [InvoiceController::class, 'board'])->name('invoices.board');
Route::get('invoices/selectable-abschlaege', [InvoiceController::class, 'selectableAbschlaege'])->name('invoices.selectable-abschlaege');
Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
// JSON status transition used by the kanban board. Narrow transitions only —
// full edits must go through update() or createCorrection().
Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'setStatus'])->name('invoices.set-status');
// Admin/super-admin only — fill any company-snapshot fields that are missing
// (e.g. legal_form/display_name on invoices created before those fields
// existed). Never overwrites existing values; every call is audit-logged.
Route::post('invoices/{invoice}/refresh-snapshot', [InvoiceController::class, 'refreshSnapshot'])->name('invoices.refresh-snapshot');
Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
Route::get('invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');

#Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');

// Audit Log Route
Route::get('invoices/{invoice}/audit-log', [InvoiceController::class, 'auditLog'])->name('invoices.audit-log');

// Reminder History (read-only, not PDF — cheap)
Route::get('invoices/{invoice}/reminder-history', [InvoiceController::class, 'reminderHistory'])->name('invoices.reminder-history');

// Expensive endpoints (PDF / XML generation, mail dispatch). Throttled to
// prevent a compromised or misbehaving session from DoSing the host via
// repeated DomPDF renders.
Route::middleware('throttle:30,1')->group(function () {
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/send-reminder', [InvoiceController::class, 'sendReminder'])->name('invoices.send-reminder');
    Route::get('invoices/{invoice}/xrechnung', [InvoiceController::class, 'downloadXRechnung'])->name('invoices.xrechnung');
    Route::get('invoices/{invoice}/zugferd', [InvoiceController::class, 'downloadZugferd'])->name('invoices.zugferd');
});

// Correction Routes
Route::post('invoices/{invoice}/correction', [InvoiceController::class, 'createCorrection'])->name('invoices.create-correction');

// Invoice Layout Routes
Route::prefix('invoice-layouts')->name('invoice-layouts.')->group(function () {
    Route::get('/', [InvoiceLayoutController::class, 'index'])->name('index');
    Route::post('/', [InvoiceLayoutController::class, 'store'])->name('store');
    Route::put('/{invoiceLayout}', [InvoiceLayoutController::class, 'update'])->name('update');
    Route::delete('/{invoiceLayout}', [InvoiceLayoutController::class, 'destroy'])->name('destroy');
    Route::post('/{invoiceLayout}/set-default', [InvoiceLayoutController::class, 'setDefault'])->name('set-default');
    Route::post('/{invoiceLayout}/duplicate', [InvoiceLayoutController::class, 'duplicate'])->name('duplicate');

    // Preview routes render a full PDF — throttle them so the layout editor
    // can't hammer DomPDF via repeated previews.
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/{invoiceLayout}/preview', [InvoiceLayoutController::class, 'preview'])->name('preview');
        Route::post('/preview-live', [InvoiceLayoutController::class, 'previewLive'])->name('preview-live');
        Route::post('/preview-live-pdf', [InvoiceLayoutController::class, 'previewLivePdf'])->name('preview-live-pdf');
    });
});

// Redirect settings route to invoice-layouts
Route::get('/settings/invoice-layouts', function () {
    return redirect()->route('invoice-layouts.index');
})->name('settings.invoice-layouts');

// Recurring invoices (Abo-Rechnungen). Route-model binding parameter is
// `recurringInvoice` because the controller method signatures use that name.
Route::prefix('recurring-invoices')->name('recurring-invoices.')->group(function () {
    Route::get('/',                                [RecurringInvoiceController::class, 'index'])->name('index');
    Route::get('/create',                          [RecurringInvoiceController::class, 'create'])->name('create');
    Route::post('/',                               [RecurringInvoiceController::class, 'store'])->name('store');
    Route::get('/{recurringInvoice}',              [RecurringInvoiceController::class, 'show'])->name('show');
    Route::get('/{recurringInvoice}/edit',         [RecurringInvoiceController::class, 'edit'])->name('edit');
    Route::put('/{recurringInvoice}',              [RecurringInvoiceController::class, 'update'])->name('update');
    Route::delete('/{recurringInvoice}',           [RecurringInvoiceController::class, 'destroy'])->name('destroy');
    Route::post('/{recurringInvoice}/pause',       [RecurringInvoiceController::class, 'pause'])->name('pause');
    Route::post('/{recurringInvoice}/resume',      [RecurringInvoiceController::class, 'resume'])->name('resume');

    // Immediate generation is expensive (same lock + number generation as
    // `invoices.store`). Throttle it so a runaway click cannot hammer the
    // company row.
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/{recurringInvoice}/run-now', [RecurringInvoiceController::class, 'runNow'])->name('run-now');
    });
});
