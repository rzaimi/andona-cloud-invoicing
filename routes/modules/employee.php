<?php

use App\Modules\Employee\Controllers\EmployeePortalController;
use App\Modules\User\Controllers\UserController;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------
// Employee self-service portal (role = employee)
// -----------------------------------------------------------------
Route::middleware(['auth', 'role:employee'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/documents', [EmployeePortalController::class, 'documents'])->name('documents');
    Route::get('/documents/{document}/download', [EmployeePortalController::class, 'download'])->name('documents.download');
});

// -----------------------------------------------------------------
// Admin: view & upload documents for a specific employee (user)
// -----------------------------------------------------------------
Route::middleware(['auth', 'can:manage_employee_documents'])->group(function () {
    Route::get('users/{user}/documents', [UserController::class, 'documents'])->name('users.documents');
});
