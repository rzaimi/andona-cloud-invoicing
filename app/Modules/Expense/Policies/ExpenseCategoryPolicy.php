<?php

namespace App\Modules\Expense\Policies;

use App\Modules\Expense\Models\ExpenseCategory;
use App\Modules\User\Models\User;

class ExpenseCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function view(User $user, ExpenseCategory $category): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $user->company_id === $category->company_id;
    }

    public function create(User $user): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $user->company_id !== null && $user->hasRole('admin');
    }

    public function update(User $user, ExpenseCategory $category): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $user->company_id === $category->company_id && $user->hasRole('admin');
    }

    public function delete(User $user, ExpenseCategory $category): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $user->company_id === $category->company_id && $user->hasRole('admin');
    }
}
