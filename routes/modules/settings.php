<?php

use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\ImportController;
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
    Route::get('invoice-sent', [EmailTemplateController::class, 'previewInvoiceSent'])->name('invoice-sent');
    Route::get('invoice-reminder', [EmailTemplateController::class, 'previewInvoiceReminder'])->name('invoice-reminder');
    Route::get('offer-sent', [EmailTemplateController::class, 'previewOfferSent'])->name('offer-sent');
    Route::get('offer-accepted', [EmailTemplateController::class, 'previewOfferAccepted'])->name('offer-accepted');
    Route::get('offer-reminder', [EmailTemplateController::class, 'previewOfferReminder'])->name('offer-reminder');
    Route::get('payment-received', [EmailTemplateController::class, 'previewPaymentReceived'])->name('payment-received');
    Route::get('welcome', [EmailTemplateController::class, 'previewWelcome'])->name('welcome');
    Route::get('friendly-reminder', [EmailTemplateController::class, 'previewFriendlyReminder'])->name('friendly-reminder');
    Route::get('mahnung-1', [EmailTemplateController::class, 'previewMahnung1'])->name('mahnung-1');
    Route::get('mahnung-2', [EmailTemplateController::class, 'previewMahnung2'])->name('mahnung-2');
    Route::get('mahnung-3', [EmailTemplateController::class, 'previewMahnung3'])->name('mahnung-3');
    Route::get('inkasso', [EmailTemplateController::class, 'previewInkasso'])->name('inkasso');
});
// Redirect old routes to unified page with tab parameter
Route::get('/settings/erechnung', function () {
    return redirect()->route('settings.index', ['tab' => 'erechnung']);
})->name('settings.erechnung');
Route::post('/settings/erechnung', [SettingsController::class, 'updateErechnung'])->name('settings.erechnung.update');
Route::post('/settings/gaeb', [SettingsController::class, 'updateGaeb'])->name('settings.gaeb.update');
Route::post('/settings/datev', [SettingsController::class, 'updateDatev'])->name('settings.datev.update');

Route::get('/settings/notifications', function () {
    return redirect()->route('settings.index', ['tab' => 'notifications']);
})->name('settings.notifications');

Route::get('/settings/payment-methods', function () {
    return redirect()->route('settings.index', ['tab' => 'payment-methods']);
})->name('settings.payment-methods');
Route::post('/settings/payment-methods', [SettingsController::class, 'updatePaymentMethods'])->name('settings.payment-methods.update');

Route::post('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');

// Company Information (for admins to edit their own company)
Route::post('/settings/company-info', [SettingsController::class, 'updateCompanyInfo'])->name('settings.company-info.update');

// Logo-only upload (used from invoice/offer layout settings dialog)
Route::post('/settings/company-logo', [SettingsController::class, 'updateCompanyLogo'])->name('settings.company-logo.update');

// Company settings CRUD (advanced)
Route::post('/settings/company-settings', [SettingsController::class, 'storeCompanySetting'])->name('settings.company-settings.store');
Route::put('/settings/company-settings/{companySetting}', [SettingsController::class, 'updateCompanySetting'])->name('settings.company-settings.update');
Route::delete('/settings/company-settings/{companySetting}', [SettingsController::class, 'destroyCompanySetting'])->name('settings.company-settings.destroy');

Route::middleware('can:manage_settings')->group(function () {
    Route::get('/settings/import-export', [ImportController::class, 'showImportExportPage'])->name('settings.import-export');
});
