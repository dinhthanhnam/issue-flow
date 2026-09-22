<?php

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(
        Request $request,
        Workspace $workspace
    ): JsonResponse {
        Gate::authorize('viewAny', [Project::class, $workspace]);

        $projects = $workspace->projects()
            ->when(
                ! $workspace->hasAnyRole($request->user(), [
                    WorkspaceRole::Owner,
                    WorkspaceRole::Admin,
                ]),
                fn ($query) => $query->whereHas(
                    'users',
                    fn ($users) => $users->whereKey($request->user()->id),
                ),
            )
            ->latest('projects.created_at')
            ->latest('projects.id')
            ->get();

        return ProjectResource::collection($projects)->response();
    }

    public function store(
        StoreProjectRequest $request,
        Workspace $workspace,
    ): JsonResponse {
        $data = $request->validated();

        $project = DB::transaction(function () use ($data, $request, $workspace): Project {
            $project = Project::query()->create([
                ...$data,
                'workspace_id' => $workspace->id,
            ]);

            $project->users()->attach($request->user(), [
                'role' => ProjectRole::Lead->value,
            ]);

            return $project;
        });

        return new ProjectResource($project)
            ->response()
            ->setStatusCode(201);
    }

    public function show(
        Project $project
    ): JsonResponse {
        Gate::authorize('view', $project);

        return new ProjectResource($project)->response();
    }

    public function update(
        UpdateProjectRequest $request,
        Project $project,
    ): JsonResponse {
        $data = $request->validated();
        $project->update($data);

        return new ProjectResource($project)->response();
    }
}
