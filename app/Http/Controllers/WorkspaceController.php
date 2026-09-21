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
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class WorkspaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $workspaces = $request->user()
            ->workspaces()
            ->withPivot('role')
            ->latest('workspaces.created_at')
            ->get();

        return WorkspaceResource::collection($workspaces);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(CreateWorkspaceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $workspace = Workspace::create($data);

        return response()->json(new WorkspaceResource($workspace), 201);
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

        return response()->json(new WorkspaceResource($workspace), 204);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Workspace $workspace): WorkspaceResource
    {
        Gate::authorize('view', $workspace);

        return new WorkspaceResource($workspace);
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

        return response()->json(new WorkspaceResource($workspace));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Workspace $workspace)
    {
        //
    }

    public function archive(Workspace $workspace): WorkspaceResource
    {
        Gate::authorize('archive', $workspace);

        $workspace->update([
            'archived_at' => now(),
        ]);

        return new WorkspaceResource($workspace);
    }

    public function restore(Workspace $workspace): WorkspaceResource
    {
        Gate::authorize('restore', $workspace);

        $workspace->update([
            'archived_at' => null,
        ]);

        return new WorkspaceResource($workspace);
    }
}
