<?php

namespace App\Modules\Invoice\Policies;

use App\Modules\Invoice\Models\Invoice;
use App\Modules\User\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->company_id === $invoice->company_id || $user->hasPermissionTo('manage_companies');
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->company_id === $invoice->company_id || $user->hasPermissionTo('manage_companies');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->company_id === $invoice->company_id || $user->hasPermissionTo('manage_companies');
    }
}
