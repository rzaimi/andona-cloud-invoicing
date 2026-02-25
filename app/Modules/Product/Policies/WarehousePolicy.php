<?php

namespace App\Modules\Product\Policies;

use App\Modules\Product\Models\Warehouse;
use App\Modules\User\Models\User;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $user->company_id === $warehouse->company_id;
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $user->company_id === $warehouse->company_id;
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $user->company_id === $warehouse->company_id;
    }
}
