<?php

use App\Modules\Expense\Controllers\ExpenseController;
use App\Modules\Expense\Controllers\ExpenseCategoryController;
use App\Modules\Expense\Controllers\ExpenseReportController;

// Expense CRUD routes
Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
Route::get('expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');

// Expense Category routes (must be before expenses/{expense} to avoid route conflicts)
Route::get('expenses/categories', [ExpenseCategoryController::class, 'index'])->name('expenses.categories.index');
Route::post('expenses/categories', [ExpenseCategoryController::class, 'store'])->name('expenses.categories.store');
Route::put('expenses/categories/{category}', [ExpenseCategoryController::class, 'update'])->name('expenses.categories.update');
Route::delete('expenses/categories/{category}', [ExpenseCategoryController::class, 'destroy'])->name('expenses.categories.destroy');

// Expense detail routes (must be after categories to avoid route conflicts)
Route::get('expenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');
Route::get('expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
Route::get('expenses/{expense}/receipt', [ExpenseController::class, 'downloadReceipt'])->name('expenses.receipt');

// Expense Report routes
Route::get('reports/expenses', [ExpenseReportController::class, 'summary'])->name('reports.expenses');
Route::get('reports/profit', [ExpenseReportController::class, 'profit'])->name('reports.profit');
Route::get('reports/vat', [ExpenseReportController::class, 'vat'])->name('reports.vat');

