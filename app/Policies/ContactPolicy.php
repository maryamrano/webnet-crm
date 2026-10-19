<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

/** Contacts inherit ALL visibility from their company (approved rule). */
class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->can('view', $contact->company);
    }

    public function create(User $user): bool
    {
        return true; // the target company is authorized separately in the controller
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->can('update', $contact->company);
    }

    /** Deleting contacts is Administrator-only (approved rule A2). */
    public function delete(User $user, Contact $contact): bool
    {
        return $user->isAdmin();
    }
}
