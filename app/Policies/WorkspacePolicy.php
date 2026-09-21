<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
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
    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->hasMember($user);
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
    public function update(User $user, Workspace $workspace): bool
    {
        return $this->hasRole(
            $user,
            $workspace,
            [WorkspaceRole::Owner, WorkspaceRole::Admin],
        );
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        return $this->hasRole(
            $user,
            $workspace,
            [WorkspaceRole::Owner, WorkspaceRole::Admin],
        );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Workspace $workspace): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function archive(User $user, Workspace $workspace): bool
    {
        return $this->hasRole(
            $user,
            $workspace,
            [WorkspaceRole::Owner],
        );
    }

    public function restore(User $user, Workspace $workspace): bool
    {
        return $this->hasRole(
            $user,
            $workspace,
            [WorkspaceRole::Owner],
        );
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Workspace $workspace): bool
    {
        return false;
    }

    private function hasRole(
        User $user,
        Workspace $workspace,
        array $roles,
    ): bool {
        return $workspace->users()
            ->whereKey($user->id)
            ->wherePivotIn(
                'role',
                array_map(fn ($role) => $role->value, $roles),
            )
            ->exists();
    }
}
