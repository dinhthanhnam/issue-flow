<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\User;

class IssuePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Issue $issue): bool
    {
        return $issue->project->workspace->hasMember($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Issue $issue): bool
    {
        return $issue->project
            ->workspace
            ->users()
            ->whereKey($user->id)
            ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Issue $issue): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Issue $issue): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Issue $issue): bool
    {
        return false;
    }

    public function comment(User $user, Issue $issue): bool
    {
        return $issue->project
            ->workspace
            ->users()
            ->whereKey($user->id)
            ->exists();
    }
}
