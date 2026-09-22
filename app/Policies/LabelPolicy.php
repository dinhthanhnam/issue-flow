<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\Label;
use App\Models\User;
use App\Models\Workspace;

class LabelPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Workspace $workspace): bool
    {
        return $workspace->hasMember($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Label $label): bool
    {
        return $label->workspace->hasMember($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Workspace $workspace): bool
    {
        return $workspace->hasAnyRole($user, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Label $label): bool
    {
        return $label->workspace->hasAnyRole($user, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
        ]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Label $label): bool
    {
        return $this->update($user, $label);
    }
}
