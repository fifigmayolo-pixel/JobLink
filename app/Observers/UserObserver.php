<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Spatie's role:/@role() checks read from its own roles tables, which
     * are separate from the `role` enum column everything else in this
     * project checks directly. This keeps the two in sync automatically,
     * so both systems agree on every user without duplicating role logic
     * anywhere else.
     */
    public function saved(User $user): void
    {
        if ($user->wasRecentlyCreated || $user->wasChanged('role')) {
            $user->syncRoles([$user->role]);
        }
    }
}