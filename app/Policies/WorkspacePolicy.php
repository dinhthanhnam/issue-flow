<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->hasMember($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $workspace->hasAnyRole(
            $user,
            [WorkspaceRole::Owner, WorkspaceRole::Admin],
        );
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        return $workspace->hasAnyRole(
            $user,
            [WorkspaceRole::Owner, WorkspaceRole::Admin],
        );
    }

    public function manageMember(User $user, Workspace $workspace, User $target): bool
    {
        if ($workspace->hasRole($user, WorkspaceRole::Owner)) {
            return true;
        }

        return $workspace->hasRole($user, WorkspaceRole::Admin)
            && $workspace->hasRole($target, WorkspaceRole::Member);
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return false;
    }

    public function archive(User $user, Workspace $workspace): bool
    {
        return $workspace->hasAnyRole(
            $user,
            [WorkspaceRole::Owner],
        );
    }

    public function restore(User $user, Workspace $workspace): bool
    {
        return $workspace->hasAnyRole(
            $user,
            [WorkspaceRole::Owner],
        );
    }
}
