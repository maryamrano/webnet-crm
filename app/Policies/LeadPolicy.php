<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // any authenticated role; the query itself is scoped per user
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->isAdmin() || $user->isManager() || $lead->assigned_to === $user->id || $lead->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Lead $lead): bool
    {
        if ($lead->isConverted()) {
            // Converted leads are historical records; only Admin/Manager may still edit them.
            return $user->isAdmin() || $user->isManager();
        }

        return $this->view($user, $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function convert(User $user, Lead $lead): bool
    {
        return ! $lead->isConverted() && $this->view($user, $lead);
    }
}
