<?php

namespace App\Actions\Workspaces;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class CreateWorkspace
{
    public function handle(User $owner, string $name, string $slug): Workspace
    {
        return DB::transaction(function () use ($name, $owner, $slug): Workspace {
            $workspace = Workspace::query()->create([
                'name' => $name,
                'slug' => $slug,
            ]);

            $workspace->users()->attach($owner, [
                'role' => WorkspaceRole::Owner->value,
            ]);

            return $workspace;
        });
    }
}
