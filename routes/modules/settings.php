<?php
use App\Modules\Settings\Controllers\SettingsController;

Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
Route::get('/settings/email', [SettingsController::class, 'email'])->name('settings.email');
Route::post('/settings/email', [SettingsController::class, 'updateEmail'])->name('settings.email.update');
Route::get('/settings/reminders', [SettingsController::class, 'reminders'])->name('settings.reminders');
Route::post('/settings/reminders', [SettingsController::class, 'updateReminders'])->name('settings.reminders.update');
Route::get('/settings/email-logs', [SettingsController::class, 'emailLogs'])->name('settings.email-logs');
Route::get('/settings/erechnung', [SettingsController::class, 'erechnung'])->name('settings.erechnung');
Route::post('/settings/erechnung', [SettingsController::class, 'updateErechnung'])->name('settings.erechnung.update');
// invoice-layouts route moved to routes/modules/invoices.php (redirects to invoice-layouts.index)
// offer-layouts route moved to routes/modules/offers.php (redirects to offer-layouts.index)
Route::get('/settings/notifications', [SettingsController::class, 'notifications'])->name('settings.notifications');
Route::get('/settings/payment-methods', [SettingsController::class, 'paymentMethods'])->name('settings.payment-methods');
