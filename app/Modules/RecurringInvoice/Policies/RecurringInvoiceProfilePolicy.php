<?php

namespace App\Modules\RecurringInvoice\Policies;

use App\Modules\RecurringInvoice\Models\RecurringInvoiceProfile;
use App\Modules\User\Models\User;

class RecurringInvoiceProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function view(User $user, RecurringInvoiceProfile $profile): bool
    {
        return $this->belongsToSameCompany($user, $profile);
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function update(User $user, RecurringInvoiceProfile $profile): bool
    {
        return $this->belongsToSameCompany($user, $profile);
    }

    public function delete(User $user, RecurringInvoiceProfile $profile): bool
    {
        return $this->belongsToSameCompany($user, $profile);
    }

    /**
     * Admin-only trigger that generates an invoice immediately instead of
     * waiting for the schedule. Same company rules; no extra role gate yet —
     * any tenant member can already change the `next_run_date` by editing.
     */
    public function runNow(User $user, RecurringInvoiceProfile $profile): bool
    {
        return $this->belongsToSameCompany($user, $profile);
    }

    private function belongsToSameCompany(User $user, RecurringInvoiceProfile $profile): bool
    {
        return $user->company_id === $profile->company_id
            || $user->hasPermissionTo('manage_companies');
    }
}
