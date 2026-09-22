<?php

namespace App\Http\Controllers;

use App\Actions\Workspaces\CreateWorkspace;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WorkspaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspaces = $request->user()
            ->workspaces()
            ->withPivot('role')
            ->latest('workspaces.created_at')
            ->get();

        return WorkspaceResource::collection($workspaces)->response();
    }

    public function store(
        StoreWorkspaceRequest $request,
        CreateWorkspace $createWorkspace,
    ): JsonResponse {
        $workspace = $createWorkspace->handle(
            owner: $request->user(),
            name: $request->string('name')->toString(),
            slug: $request->string('slug')->toString(),
        );

        return new WorkspaceResource($workspace)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Workspace $workspace): JsonResponse
    {
        Gate::authorize('view', $workspace);

        return new WorkspaceResource($workspace)->response();
    }

    public function update(
        UpdateWorkspaceRequest $request,
        Workspace $workspace,
    ): JsonResponse {
        $workspace->update($request->validated());

        return new WorkspaceResource($workspace)->response();
    }

    public function archive(Workspace $workspace): JsonResponse
    {
        Gate::authorize('archive', $workspace);

        $workspace->archived_at = now();
        $workspace->save();

        return new WorkspaceResource($workspace)->response();
    }

    public function restore(Workspace $workspace): JsonResponse
    {
        Gate::authorize('restore', $workspace);

        $workspace->archived_at = null;
        $workspace->save();

        return new WorkspaceResource($workspace)->response();
    }
}
