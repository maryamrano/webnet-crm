<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;

/**
 * Minimal deal authorization for Phase 4: it exists so activities attached to a
 * deal can never leak a deal the user may not see. Full deal UI arrives in Phase 5.
 */
class DealPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Deal $deal): bool
    {
        return $user->isAdmin() || $user->isManager()
            || $deal->owner_id === $user->id || $deal->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Deal $deal): bool
    {
        return $this->view($user, $deal);
    }

    public function delete(User $user, Deal $deal): bool
    {
        return $user->isAdmin() || $user->isManager();
    }
}
