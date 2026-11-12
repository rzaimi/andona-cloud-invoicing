<?php

use App\Modules\Customer\Controllers\CustomerContactController;
use App\Modules\Customer\Controllers\CustomerController;

Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
// JSON search for the ⌘K command palette.
Route::get('customers/search', [CustomerController::class, 'search'])->name('customers.search');
Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
Route::post('customers/{customer}/duplicate', [CustomerController::class, 'duplicate'])->name('customers.duplicate');

// Contact persons (Ansprechpartner). The GET is JSON for the send dialogs.
Route::get('customers/{customer}/contacts', [CustomerContactController::class, 'index'])->name('customers.contacts.index');
Route::post('customers/{customer}/contacts', [CustomerContactController::class, 'store'])->name('customers.contacts.store');
Route::put('customers/{customer}/contacts/{contact}', [CustomerContactController::class, 'update'])->name('customers.contacts.update');
Route::delete('customers/{customer}/contacts/{contact}', [CustomerContactController::class, 'destroy'])->name('customers.contacts.destroy');
