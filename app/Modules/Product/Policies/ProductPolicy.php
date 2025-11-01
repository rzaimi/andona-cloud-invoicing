<?php

namespace App\Modules\Product\Policies;

use App\Modules\Product\Models\Product;
use App\Modules\User\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->company_id === $product->company_id || $user->hasPermissionTo('manage_companies');
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->company_id === $product->company_id || $user->hasPermissionTo('manage_companies');
    }

    public function delete(User $user, Product $product): bool
    {
        return ($user->company_id === $product->company_id || $user->hasPermissionTo('manage_companies')) && ($user->hasPermissionTo('manage_users') || $user->hasRole('admin'));
    }
}
