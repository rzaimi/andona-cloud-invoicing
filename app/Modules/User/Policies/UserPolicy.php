<?php

namespace App\Modules\User\Policies;

use App\Modules\User\Models\User;

class UserPolicy
{
    public function viewAny(User $currentUser): bool
    {
        return true;
    }

    public function view(User $currentUser, User $targetUser): bool
    {
        if ($currentUser->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $currentUser->company_id === $targetUser->company_id;
    }

    public function create(User $currentUser): bool
    {
        return $currentUser->hasPermissionTo('manage_users')
            || $currentUser->hasPermissionTo('manage_companies');
    }

    public function update(User $currentUser, User $targetUser): bool
    {
        // Super admin can edit anyone
        if ($currentUser->hasPermissionTo('manage_companies')) {
            return true;
        }

        // Admin with manage_users can edit non-admin users in same company (and themselves)
        if ($currentUser->hasPermissionTo('manage_users')
            && $targetUser->company_id === $currentUser->company_id
        ) {
            return !$targetUser->hasRole('admin') || $targetUser->id === $currentUser->id;
        }

        // Users can only edit themselves
        return $currentUser->id === $targetUser->id;
    }

    public function delete(User $currentUser, User $targetUser): bool
    {
        // Cannot delete yourself
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        // Super admin can delete anyone (except themselves, covered above)
        if ($currentUser->hasPermissionTo('manage_companies')) {
            return true;
        }

        // Admin with manage_users can delete non-admin users in same company
        if ($currentUser->hasPermissionTo('manage_users')
            && $targetUser->company_id === $currentUser->company_id
        ) {
            return !$targetUser->hasRole('admin');
        }

        return false;
    }
}
