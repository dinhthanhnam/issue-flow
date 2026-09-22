<?php

namespace App\Actions\Projects;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class CreateProject
{
    public function handle(
        Workspace $workspace,
        User $creator,
        string $name,
        string $key,
        ?string $description,
    ): Project {
        return DB::transaction(function () use ($creator, $description, $key, $name, $workspace): Project {
            $project = $workspace->projects()->create([
                'name' => $name,
                'key' => $key,
                'description' => $description,
            ]);

            $project->users()->attach($creator, [
                'role' => ProjectRole::Lead->value,
            ]);

            return $project;
        });
    }
}
