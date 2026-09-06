<?php

use App\Modules\Mahnung\Controllers\MahnungController;

Route::get('dunning', [MahnungController::class, 'index'])->name('dunning.index');

// Legacy German URLs (bookmarks, emailed links) — permanent redirects.
Route::redirect('mahnungen', '/dunning', 301);
Route::get('mahnungen/{invoice}', fn (string $invoice) => redirect()->route('dunning.show', $invoice, 301));
Route::get('dunning/{invoice}', [MahnungController::class, 'show'])->name('dunning.show');
Route::get('dunning/{invoice}/dossier', [MahnungController::class, 'dossier'])->name('dunning.dossier');

Route::middleware('throttle:30,1')->group(function () {
    Route::post('dunning/send-due', [MahnungController::class, 'sendDue'])->name('dunning.send-due');
    Route::post('dunning/{invoice}', [MahnungController::class, 'store'])->name('dunning.store');
    Route::post('dunning/{invoice}/pause', [MahnungController::class, 'pause'])->name('dunning.pause');
    Route::post('dunning/{invoice}/resume', [MahnungController::class, 'resume'])->name('dunning.resume');
});
