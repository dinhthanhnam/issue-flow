<?php

namespace App\Http\Controllers;

use App\Actions\Workspaces\RemoveWorkspaceMember;
use App\Http\Requests\StoreWorkspaceMemberRequest;
use App\Http\Requests\UpdateWorkspaceMemberRequest;
use App\Http\Resources\WorkspaceMemberResource;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class WorkspaceMemberController extends Controller
{
    public function index(Workspace $workspace): JsonResponse
    {
        Gate::authorize('view', $workspace);

        $members = $workspace->users()
            ->withPivot('role')
            ->get();

        return WorkspaceMemberResource::collection($members)->response();
    }

    public function store(
        StoreWorkspaceMemberRequest $request,
        Workspace $workspace,
    ): JsonResponse {
        $data = $request->validated();

        $workspace->users()->attach($data['user_id'], [
            'role' => $data['role'],
        ]);

        $member = $workspace->users()
            ->withPivot('role')
            ->findOrFail($data['user_id']);

        return new WorkspaceMemberResource($member)
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateWorkspaceMemberRequest $request,
        Workspace $workspace,
        User $user,
    ): JsonResponse {
        $workspace->users()->updateExistingPivot($user->id, [
            'role' => $request->validated('role'),
        ]);

        $member = $workspace->users()
            ->withPivot('role')
            ->findOrFail($user->id);

        return new WorkspaceMemberResource($member)->response();
    }

    public function destroy(
        Workspace $workspace,
        User $user,
        RemoveWorkspaceMember $removeWorkspaceMember,
    ): JsonResponse {
        abort_unless($workspace->hasMember($user), 404);

        Gate::authorize('manageMember', [$workspace, $user]);

        $removeWorkspaceMember->handle($workspace, $user);

        return response()->json(status: 204);
    }
}
