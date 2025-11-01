<?php

namespace App\Modules\Customer\Policies;

use App\Modules\Customer\Models\Customer;
use App\Modules\User\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->company_id === $customer->company_id || $user->hasPermissionTo('manage_companies');
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->company_id === $customer->company_id || $user->hasPermissionTo('manage_companies');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->company_id === $customer->company_id || $user->hasPermissionTo('manage_companies');
    }
}
