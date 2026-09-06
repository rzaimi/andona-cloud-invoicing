<?php

use App\Modules\Mahnung\Controllers\MahnungController;

Route::get('mahnungen', [MahnungController::class, 'index'])->name('mahnungen.index');
Route::get('mahnungen/{invoice}', [MahnungController::class, 'show'])->name('mahnungen.show');

Route::middleware('throttle:30,1')->group(function () {
    Route::post('mahnungen/{invoice}', [MahnungController::class, 'store'])->name('mahnungen.store');
});
