<?php

namespace App\Modules\Offer\Policies;

use App\Modules\Offer\Models\OfferLayout;
use App\Modules\User\Models\User;

class OfferLayoutPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function view(User $user, OfferLayout $layout): bool
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

    public function update(User $user, OfferLayout $layout): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $user->company_id === $layout->company_id;
    }

    public function delete(User $user, OfferLayout $layout): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $user->company_id === $layout->company_id;
    }
}
