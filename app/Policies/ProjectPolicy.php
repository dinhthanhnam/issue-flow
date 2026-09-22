<?php

namespace App\Policies;

use App\Enums\ProjectRole;
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
        return $project->isAccessibleBy($user);
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
        return $project->isWorkspaceManager($user)
            || $project->hasProjectRole($user, ProjectRole::Lead);
    }

    public function viewIssues(User $user, Project $project): bool
    {
        return $project->isAccessibleBy($user);
    }

    public function createIssue(User $user, Project $project): bool
    {
        return $project->canContribute($user);
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $project->canManageMembers($user);
    }

    public function manageMember(User $user, Project $project, User $target): bool
    {
        if ($project->isWorkspaceManager($user)) {
            return true;
        }

        return $project->hasProjectRole($user, ProjectRole::Lead)
            && ! $project->hasProjectRole($target, ProjectRole::Lead);
    }
}
