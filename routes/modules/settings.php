<?php
use App\Modules\Settings\Controllers\SettingsController;

Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
// invoice-layouts route moved to routes/modules/invoices.php (redirects to invoice-layouts.index)
// offer-layouts route moved to routes/modules/offers.php (redirects to offer-layouts.index)
Route::get('/settings/notifications', [SettingsController::class, 'notifications'])->name('settings.notifications');
Route::get('/settings/payment-methods', [SettingsController::class, 'paymentMethods'])->name('settings.payment-methods');
