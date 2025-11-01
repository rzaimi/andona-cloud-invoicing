<?php

namespace App\Modules\Offer\Policies;

use App\Modules\Offer\Models\Offer;
use App\Modules\User\Models\User;

class OfferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function view(User $user, Offer $offer): bool
    {
        return $user->company_id === $offer->company_id || $user->hasPermissionTo('manage_companies');
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function update(User $user, Offer $offer): bool
    {
        return $user->company_id === $offer->company_id || $user->hasPermissionTo('manage_companies');
    }

    public function delete(User $user, Offer $offer): bool
    {
        return $user->company_id === $offer->company_id || $user->hasPermissionTo('manage_companies');
    }
}

