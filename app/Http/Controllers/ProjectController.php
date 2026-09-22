<?php

namespace App\Http\Controllers;

use App\Actions\Projects\CreateProject;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\Workspace;
use App\Queries\Projects\ProjectIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(
        Request $request,
        Workspace $workspace,
        ProjectIndexQuery $projectIndexQuery,
    ): JsonResponse {
        Gate::authorize('viewAny', [Project::class, $workspace]);

        $projects = $projectIndexQuery->get($workspace, $request->user());

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
