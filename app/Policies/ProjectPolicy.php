<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

class ProjectPolicy
{
    public function viewAny(User $user, Workspace $workspace): bool
    {
        return $workspace->hasMember($user);
    }

    public function view(User $user, Project $project): bool
    {
        return $project->workspace->hasMember($user);
    }

    public function create(User $user, Workspace $workspace): bool
    {
        return $workspace->hasAnyRole($user, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
        ]);
    }

    public function update(User $user, Project $project): bool
    {
        return $project->workspace->hasAnyRole($user, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
        ]);
    }

    public function viewIssues(User $user, Project $project): bool
    {
        return $project->workspace->hasMember($user);
    }

    public function createIssue(User $user, Project $project): bool
    {
        return $project->workspace->hasMember($user);
    }
}
