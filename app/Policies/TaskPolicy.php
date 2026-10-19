<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // list query is scoped per user
    }

    public function view(User $user, Task $task): bool
    {
        return $user->isAdmin() || $user->isManager()
            || $task->assigned_to === $user->id || $task->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return true; // parent access (if any) is enforced in the controller
    }

    public function update(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    /** Admin, Manager, or the task's creator (approved delete rules). */
    public function delete(User $user, Task $task): bool
    {
        return $user->isAdmin() || $user->isManager() || $task->created_by === $user->id;
    }
}
