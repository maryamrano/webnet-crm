<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // list query is scoped per user
    }

    public function view(User $user, Company $company): bool
    {
        return $user->isAdmin() || $user->isManager()
            || $company->owner_id === $user->id || $company->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Company $company): bool
    {
        return $this->view($user, $company);
    }

    /** Deleting companies is Administrator-only (approved rule A2). */
    public function delete(User $user, Company $company): bool
    {
        return $user->isAdmin();
    }
}
