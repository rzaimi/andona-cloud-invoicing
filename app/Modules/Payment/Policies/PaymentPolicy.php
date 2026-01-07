<?php

namespace App\Modules\Payment\Policies;

use App\Modules\Payment\Models\Payment;
use App\Modules\User\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->company_id === $payment->company_id || $user->hasPermissionTo('manage_companies');
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->company_id === $payment->company_id || $user->hasPermissionTo('manage_companies');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->company_id === $payment->company_id || $user->hasPermissionTo('manage_companies');
    }
}




