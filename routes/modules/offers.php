<?php
use App\Modules\Offer\Controllers\OfferController;
use App\Modules\Offer\Controllers\OfferLayoutController;

Route::get('offers', [OfferController::class, 'index'])->name('offers.index');
Route::get('offers/create', [OfferController::class, 'create'])->name('offers.create');
Route::post('offers', [OfferController::class, 'store'])->name('offers.store');

// These routes must come after 'create' to avoid route model binding conflicts
Route::get('offers/{offer}', [OfferController::class, 'show'])->name('offers.show');
Route::get('offers/{offer}/edit', [OfferController::class, 'edit'])->name('offers.edit');
Route::put('offers/{offer}', [OfferController::class, 'update'])->name('offers.update');
Route::delete('offers/{offer}', [OfferController::class, 'destroy'])->name('offers.destroy');

Route::post('offers/{offer}/duplicate', [OfferController::class, 'duplicate'])->name('offers.duplicate');
Route::post('offers/{offer}/send', [OfferController::class, 'send'])->name('offers.send');
Route::get('offers/{offer}/pdf', [OfferController::class, 'pdf'])->name('offers.pdf');
Route::get('offers/{offer}/preview', [OfferController::class, 'preview'])->name('offers.preview');
Route::post('offers/{offer}/convert-to-invoice', [OfferController::class, 'convertToInvoice'])->name('offers.convert-to-invoice');
Route::post('offers/{offer}/accept', [OfferController::class, 'accept'])->name('offers.accept');
Route::post('offers/{offer}/reject', [OfferController::class, 'reject'])->name('offers.reject');

// Offer Layout Routes
Route::prefix('offer-layouts')->name('offer-layouts.')->group(function () {
    Route::get('/', [OfferLayoutController::class, 'index'])->name('index');
    Route::post('/', [OfferLayoutController::class, 'store'])->name('store');
    Route::post('/preview-live-pdf', [OfferLayoutController::class, 'previewLivePdf'])->name('preview-live-pdf');
    Route::get('/{offerLayout}/preview', [OfferLayoutController::class, 'preview'])->name('preview');
    Route::put('/{offerLayout}', [OfferLayoutController::class, 'update'])->name('update');
    Route::delete('/{offerLayout}', [OfferLayoutController::class, 'destroy'])->name('destroy');
    Route::post('/{offerLayout}/set-default', [OfferLayoutController::class, 'setDefault'])->name('set-default');
    Route::post('/{offerLayout}/duplicate', [OfferLayoutController::class, 'duplicate'])->name('duplicate');
});

// Redirect settings route to offer-layouts
Route::get('/settings/offer-layouts', function () {
    return redirect()->route('offer-layouts.index');
})->name('settings.offer-layouts');
