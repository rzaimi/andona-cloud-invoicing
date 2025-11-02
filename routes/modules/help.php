<?php
use App\Modules\Help\Controllers\HelpController;

Route::get('/help', [HelpController::class, 'index'])->name('help.index');
Route::get('/help/{category}', [HelpController::class, 'show'])->name('help.show');
Route::get('/help/search', [HelpController::class, 'search'])->name('help.search');
