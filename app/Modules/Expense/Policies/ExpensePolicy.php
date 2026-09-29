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
        // Super admin can view expenses across all companies
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        // Admin and user can only view expenses from their own company
        return $user->company_id === $expense->company_id;
    }

    public function create(User $user): bool
    {
        // Super admin can always create; admin/user need a company
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        // Only users who may manage expenses can create them
        return $user->company_id !== null && $user->hasPermissionTo('manage_expenses');
    }

    public function update(User $user, Expense $expense): bool
    {
        // Super admin can update any expense
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        // Admin can update only their own company's expenses
        return $user->company_id === $expense->company_id && $user->hasPermissionTo('manage_expenses');
    }

    public function delete(User $user, Expense $expense): bool
    {
        // Super admin can delete any expense
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        // Admin can delete only their own company's expenses
        return $user->company_id === $expense->company_id && $user->hasPermissionTo('manage_expenses');
    }
}
