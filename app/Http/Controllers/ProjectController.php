<?php

namespace App\Http\Controllers;

use App\Actions\Projects\CreateProject;
use App\Enums\WorkspaceRole;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        CreateProject $createProject,
    ): JsonResponse {
        $project = $createProject->handle(
            workspace: $workspace,
            creator: $request->user(),
            name: $request->string('name')->toString(),
            key: $request->string('key')->toString(),
            description: $request->input('description') === null
                ? null
                : $request->string('description')->toString(),
        );

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
