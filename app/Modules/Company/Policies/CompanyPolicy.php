<?php

namespace App\Modules\Company\Policies;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_companies');
    }

    public function view(User $user, Company $company): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        // Admins and users can only see their own company
        return $user->company_id === $company->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_companies');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->hasPermissionTo('manage_companies');
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->hasPermissionTo('manage_companies');
    }
}
