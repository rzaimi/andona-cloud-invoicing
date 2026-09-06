<?php

namespace App\Modules\Mahnung\Policies;

use App\Modules\Invoice\Models\Invoice;
use App\Modules\User\Models\User;

class MahnungPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageDunning($user);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->canManageDunning($user) && $this->sameCompany($user, $invoice);
    }

    public function send(User $user, Invoice $invoice): bool
    {
        return $this->canManageDunning($user) && $this->sameCompany($user, $invoice);
    }

    private function canManageDunning(User $user): bool
    {
        return $user->hasPermissionTo('manage_invoices')
            || $user->hasPermissionTo('manage_companies');
    }

    private function sameCompany(User $user, Invoice $invoice): bool
    {
        return $user->company_id === $invoice->company_id
            || $user->hasPermissionTo('manage_companies');
    }
}
