<?php

namespace App\Modules\Document\Policies;

use App\Modules\Document\Models\Document;
use App\Modules\User\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        // Employees use the dedicated portal; regular users need a company
        if ($user->hasPermissionTo('view_own_documents')) {
            return true;
        }

        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function view(User $user, Document $document): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        // Employee may only see documents explicitly linked to them and marked visible
        if ($user->hasPermissionTo('view_own_documents')) {
            return $document->linkable_type === 'App\\Modules\\User\\Models\\User'
                && $document->linkable_id === $user->id
                && $document->visible_to_employee;
        }

        return $user->company_id === $document->company_id;
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function update(User $user, Document $document): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $user->company_id === $document->company_id;
    }

    public function delete(User $user, Document $document): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $user->company_id === $document->company_id;
    }
}
