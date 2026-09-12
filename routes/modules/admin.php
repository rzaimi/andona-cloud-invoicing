<?php

use App\Http\Controllers\CompanyInitController;
use App\Http\Controllers\CompanyWizardController;
use App\Http\Controllers\QueueMonitorController;
use App\Http\Controllers\SystemHealthController;
use App\Modules\Company\Controllers\CompanyController;
use App\Modules\User\Controllers\CompanyContextController;
use App\Modules\User\Controllers\PermissionController;
use App\Modules\User\Controllers\RoleController;
use App\Modules\User\Controllers\UserController;

Route::middleware('can:manage_users')->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

Route::middleware('can:manage_companies')->group(function () {
    // Roles & Permissions management — superadmin only (system-wide, not per-tenant)
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

    Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::post('permissions', [PermissionController::class, 'store'])->name('permissions.store');
    Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

    Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('companies/create', [CompanyController::class, 'create'])->name('companies.create');

    // Wizard Routes (must be before resource routes to avoid conflicts)
    Route::get('companies/wizard', [CompanyWizardController::class, 'show'])->name('companies.wizard.show');
    Route::get('companies/wizard/start', [CompanyWizardController::class, 'start'])->name('companies.wizard.start');
    Route::post('companies/wizard/complete', [CompanyWizardController::class, 'complete'])->name('companies.wizard.complete');
    Route::post('companies/wizard/cancel', [CompanyWizardController::class, 'cancel'])->name('companies.wizard.cancel');

    Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
    Route::get('companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');

    // Company context switching for super admins
    Route::post('company-context/switch', [CompanyContextController::class, 'switch'])->name('company-context.switch');
    Route::get('company-context/current', [CompanyContextController::class, 'getCurrent'])->name('company-context.current');

    // System Health (super admin only)
    Route::get('system-health', [SystemHealthController::class, 'index'])->name('system-health.index');
    Route::post('system-health/run-command', [SystemHealthController::class, 'runCommand'])->name('system-health.run-command');
    Route::get('system-health/logs', [SystemHealthController::class, 'getLogs'])->name('system-health.logs');

    Route::get('system-health/queue', [QueueMonitorController::class, 'index'])->name('system-health.queue');
    Route::post('system-health/queue/retry-all', [QueueMonitorController::class, 'retryAll'])->name('system-health.queue.retry-all');
    Route::post('system-health/queue/flush-failed', [QueueMonitorController::class, 'flushFailed'])->name('system-health.queue.flush-failed');
    Route::post('system-health/queue/clear-pending', [QueueMonitorController::class, 'clearPending'])->name('system-health.queue.clear-pending');
    Route::post('system-health/queue/{uuid}/retry', [QueueMonitorController::class, 'retry'])->name('system-health.queue.retry');
    Route::delete('system-health/queue/{uuid}', [QueueMonitorController::class, 'forget'])->name('system-health.queue.forget');

    // Company Initialisation (super admin only)
    Route::get('company-init', [CompanyInitController::class, 'index'])->name('company-init.index');
    Route::post('company-init/run', [CompanyInitController::class, 'run'])->name('company-init.run');
});
