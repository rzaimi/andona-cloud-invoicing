<?php
use App\Modules\Calendar\Controllers\CalendarController;

Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
Route::post('/calendar', [CalendarController::class, 'store'])->name('calendar.store');
Route::put('/calendar/{event}', [CalendarController::class, 'update'])->name('calendar.update');
Route::delete('/calendar/{event}', [CalendarController::class, 'destroy'])->name('calendar.destroy');
