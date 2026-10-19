<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

/**
 * An activity is only ever as visible as its PARENT record. Every check first
 * resolves the parent and asks whether the user may view it — an activity can
 * never expose an otherwise inaccessible lead, company, contact or deal.
 */
class ActivityPolicy
{
    public function view(User $user, Activity $activity): bool
    {
        $parent = $activity->activityable;

        return $parent !== null && $user->can('view', $parent);
    }

    public function create(User $user): bool
    {
        return true; // parent access is enforced in the controller before creation
    }

    /** Approved rule A6: author, Manager or Admin — and only with parent access. */
    public function update(User $user, Activity $activity): bool
    {
        return $this->view($user, $activity)
            && ($user->isAdmin() || $user->isManager() || $activity->user_id === $user->id);
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $this->update($user, $activity);
    }
}
