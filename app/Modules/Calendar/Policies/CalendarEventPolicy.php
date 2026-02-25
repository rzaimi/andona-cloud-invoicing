<?php

namespace App\Modules\Calendar\Policies;

use App\Modules\Calendar\Models\CalendarEvent;
use App\Modules\User\Models\User;

class CalendarEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function view(User $user, CalendarEvent $event): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $user->company_id === $event->company_id;
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null || $user->hasPermissionTo('manage_companies');
    }

    public function update(User $user, CalendarEvent $event): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $user->company_id === $event->company_id;
    }

    public function delete(User $user, CalendarEvent $event): bool
    {
        if ($user->hasPermissionTo('manage_companies')) {
            return true;
        }

        return $user->company_id === $event->company_id;
    }
}
