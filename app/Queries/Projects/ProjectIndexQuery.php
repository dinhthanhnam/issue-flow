<?php

namespace App\Queries\Projects;

use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;

class ProjectIndexQuery
{
    /**
     * @return Collection<int, Project>
     */
    public function get(Workspace $workspace, User $user): Collection
    {
        return $workspace->projects()
            ->when(
                ! $workspace->hasAnyRole($user, [
                    WorkspaceRole::Owner,
                    WorkspaceRole::Admin,
                ]),
                fn ($query) => $query->whereHas(
                    'users',
                    fn ($users) => $users->whereKey($user->id),
                ),
            )
            ->latest('projects.created_at')
            ->latest('projects.id')
            ->get();
    }
}
