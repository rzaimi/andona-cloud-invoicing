<?php

namespace App\Modules\Expense\Policies;

use App\Modules\Expense\Models\Expense;
use App\Modules\User\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function view(User $user, Expense $expense): bool
    {
        // Admin and super admin have full access
        if ($user->hasPermissionTo('manage_companies') || $user->hasRole('admin')) {
            return true;
        }

        // Users can only view expenses from their company
        return $user->company_id === $expense->company_id;
    }

    public function create(User $user): bool
    {
        // Admin and super admin can create
        if ($user->hasPermissionTo('manage_companies') || $user->hasRole('admin')) {
            return true;
        }

        // Regular users are read-only
        return false;
    }

    public function update(User $user, Expense $expense): bool
    {
        // Admin and super admin have full access
        if ($user->hasPermissionTo('manage_companies') || $user->hasRole('admin')) {
            return true;
        }

        // Regular users are read-only
        return false;
    }

    public function delete(User $user, Expense $expense): bool
    {
        // Admin and super admin have full access
        if ($user->hasPermissionTo('manage_companies') || $user->hasRole('admin')) {
            return true;
        }

        // Regular users are read-only
        return false;
    }
}



