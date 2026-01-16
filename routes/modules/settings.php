<?php
use App\Modules\Settings\Controllers\SettingsController;

// Unified settings page with tabs
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

// Redirect old routes to unified page with tab parameter
Route::get('/settings/email', function () {
    return redirect()->route('settings.index', ['tab' => 'email']);
})->name('settings.email');
Route::post('/settings/email', [SettingsController::class, 'updateEmail'])->name('settings.email.update');

Route::get('/settings/reminders', function () {
    return redirect()->route('settings.index', ['tab' => 'reminders']);
})->name('settings.reminders');
Route::post('/settings/reminders', [SettingsController::class, 'updateReminders'])->name('settings.reminders.update');

Route::get('/settings/email-logs', [SettingsController::class, 'emailLogs'])->name('settings.email-logs');

// Email Template Previews
Route::prefix('settings/emails/preview')->name('settings.emails.preview.')->middleware(['auth'])->group(function () {
    Route::get('invoice-sent', [\App\Http\Controllers\EmailTemplateController::class, 'previewInvoiceSent'])->name('invoice-sent');
    Route::get('invoice-reminder', [\App\Http\Controllers\EmailTemplateController::class, 'previewInvoiceReminder'])->name('invoice-reminder');
    Route::get('offer-sent', [\App\Http\Controllers\EmailTemplateController::class, 'previewOfferSent'])->name('offer-sent');
    Route::get('offer-accepted', [\App\Http\Controllers\EmailTemplateController::class, 'previewOfferAccepted'])->name('offer-accepted');
    Route::get('offer-reminder', [\App\Http\Controllers\EmailTemplateController::class, 'previewOfferReminder'])->name('offer-reminder');
    Route::get('payment-received', [\App\Http\Controllers\EmailTemplateController::class, 'previewPaymentReceived'])->name('payment-received');
    Route::get('welcome', [\App\Http\Controllers\EmailTemplateController::class, 'previewWelcome'])->name('welcome');
    Route::get('friendly-reminder', [\App\Http\Controllers\EmailTemplateController::class, 'previewFriendlyReminder'])->name('friendly-reminder');
    Route::get('mahnung-1', [\App\Http\Controllers\EmailTemplateController::class, 'previewMahnung1'])->name('mahnung-1');
    Route::get('mahnung-2', [\App\Http\Controllers\EmailTemplateController::class, 'previewMahnung2'])->name('mahnung-2');
    Route::get('mahnung-3', [\App\Http\Controllers\EmailTemplateController::class, 'previewMahnung3'])->name('mahnung-3');
    Route::get('inkasso', [\App\Http\Controllers\EmailTemplateController::class, 'previewInkasso'])->name('inkasso');
});
// Redirect old routes to unified page with tab parameter
Route::get('/settings/erechnung', function () {
    return redirect()->route('settings.index', ['tab' => 'erechnung']);
})->name('settings.erechnung');
Route::post('/settings/erechnung', [SettingsController::class, 'updateErechnung'])->name('settings.erechnung.update');
Route::post('/settings/datev', [SettingsController::class, 'updateDatev'])->name('settings.datev.update');

Route::get('/settings/notifications', function () {
    return redirect()->route('settings.index', ['tab' => 'notifications']);
})->name('settings.notifications');

Route::get('/settings/payment-methods', function () {
    return redirect()->route('settings.index', ['tab' => 'payment-methods']);
})->name('settings.payment-methods');
Route::post('/settings/payment-methods', [SettingsController::class, 'updatePaymentMethods'])->name('settings.payment-methods.update');

Route::post('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');

// Company settings CRUD (advanced)
Route::post('/settings/company-settings', [SettingsController::class, 'storeCompanySetting'])->name('settings.company-settings.store');
Route::put('/settings/company-settings/{companySetting}', [SettingsController::class, 'updateCompanySetting'])->name('settings.company-settings.update');
Route::delete('/settings/company-settings/{companySetting}', [SettingsController::class, 'destroyCompanySetting'])->name('settings.company-settings.destroy');

Route::middleware('can:manage_settings')->group(function () {
    Route::get('/settings/import-export', [\App\Http\Controllers\ImportController::class, 'showImportExportPage'])->name('settings.import-export');
});
