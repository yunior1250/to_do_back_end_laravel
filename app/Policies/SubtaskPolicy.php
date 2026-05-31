<?php

namespace App\Policies;

use App\Models\Subtask;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SubtaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Subtask $subtask): bool
    {
        return $user->id === $subtask->task->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Subtask $subtask): bool
    {
        return $user->id === $subtask->task->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Subtask $subtask): bool
    {
        return $user->id === $subtask->task->user_id;
    }
}
