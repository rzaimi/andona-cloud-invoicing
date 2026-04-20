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

    /**
     * Company-boundary check only. The GoBD status lock (drafts only) is
     * enforced inside the controllers with a user-facing message so users
     * aren't hit with a blunt 403 when they follow a stale link.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        return $this->belongsToSameCompany($user, $invoice);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $this->belongsToSameCompany($user, $invoice);
    }

    /**
     * Sending / resending the invoice by email. Allowed on any status except
     * cancelled — resending a sent invoice is legitimate.
     */
    public function send(User $user, Invoice $invoice): bool
    {
        return $this->belongsToSameCompany($user, $invoice)
            && $invoice->status !== 'cancelled';
    }

    /**
     * Trigger a Mahnung. Only valid once the invoice is actually out the door
     * and unpaid — but that state check is handled in the controller so users
     * get a friendly flash message instead of a hard 403.
     */
    public function sendReminder(User $user, Invoice $invoice): bool
    {
        return $this->belongsToSameCompany($user, $invoice);
    }

    private function belongsToSameCompany(User $user, Invoice $invoice): bool
    {
        return $user->company_id === $invoice->company_id
            || $user->hasPermissionTo('manage_companies');
    }

    /**
     * Determine if user can create a Stornorechnung (correction invoice)
     * Requires higher permission level due to legal implications
     */
    public function createCorrection(User $user, Invoice $invoice): bool
    {
        // Must belong to same company
        if ($user->company_id !== $invoice->company_id && !$user->hasPermissionTo('manage_companies')) {
            return false;
        }

        // Check if user has specific permission to create corrections
        // or is an admin/super admin
        return $user->hasPermissionTo('create_stornorechnung') 
            || $user->hasRole(['admin', 'super-admin'])
            || $user->hasPermissionTo('manage_companies');
    }
}
