<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(
        Workspace $workspace
    ): JsonResponse {
        Gate::authorize('viewAny', [Project::class, $workspace]);

        $projects = $workspace->projects()
            ->latest()
            ->get();

        return ProjectResource::collection($projects)->response();
    }

    public function store(
        StoreProjectRequest $request,
        Workspace $workspace,
    ): JsonResponse {
        $data = $request->validated();

        $project = $workspace->projects()->create($data);

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
