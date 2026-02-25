<?php

namespace App\Modules\Invoice\Policies;

use App\Modules\Invoice\Models\InvoiceLayout;
use App\Modules\User\Models\User;

class InvoiceLayoutPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function view(User $user, InvoiceLayout $layout): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $user->company_id === $layout->company_id;
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function update(User $user, InvoiceLayout $layout): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $user->company_id === $layout->company_id;
    }

    public function delete(User $user, InvoiceLayout $layout): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $user->company_id === $layout->company_id;
    }
}
