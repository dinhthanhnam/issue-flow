<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Http\Requests\CreateWorkspaceRequest;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class WorkspaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $workspaces = $request->user()
            ->workspaces()
            ->withPivot('role')
            ->latest('workspaces.created_at')
            ->get();

        return WorkspaceResource::collection($workspaces)->response();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(CreateWorkspaceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $workspace = Workspace::create($data);

        return new WorkspaceResource($workspace)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        StoreWorkspaceRequest $request
    ): JsonResponse {
        $workspace = DB::transaction(function () use ($request) {
            $workspace = Workspace::create($request->validated());

            $workspace->users()->attach($request->user()->id, [
                'role' => WorkspaceRole::Owner->value,
            ]);

            return $workspace;
        });

        return new WorkspaceResource($workspace)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Workspace $workspace): JsonResponse
    {
        Gate::authorize('view', $workspace);

        return new WorkspaceResource($workspace)->response();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Workspace $workspace)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateWorkspaceRequest $request,
        Workspace $workspace,
        User $user,
    ): JsonResponse {
        $workspace->update($request->validated());

        return new WorkspaceResource($workspace)->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Workspace $workspace)
    {
        //
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
