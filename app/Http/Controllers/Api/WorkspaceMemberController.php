<?php

namespace App\Http\Controllers\Api;

use App\Actions\Workspaces\RemoveWorkspaceMember;
use App\Enums\WorkspaceRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkspaceMemberRequest;
use App\Http\Requests\UpdateWorkspaceMemberRequest;
use App\Http\Resources\WorkspaceMemberResource;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WorkspaceMemberController extends Controller
{
    public function index(
        Workspace $workspace
    ): JsonResponse {
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

        $userId = $request->integer('user_id');
        $role = WorkspaceRole::from($request->string('role')->toString());

        $workspace->users()->attach($userId, [
            'role' => $role->value,
        ]);

        $member = $workspace->users()
            ->withPivot('role')
            ->findOrFail($userId);

        return new WorkspaceMemberResource($member)
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateWorkspaceMemberRequest $request,
        Workspace $workspace,
        User $user,
    ): JsonResponse {
        $member = $workspace->users()
            ->withPivot('role')
            ->whereKey($user->id)
            ->firstOrFail();

        if ($member->pivot->role === WorkspaceRole::Owner->value) {
            abort(422, 'Owner role cannot be changed through this endpoint.');
        }

        $workspace->users()->updateExistingPivot($user->id, [
            'role' => $request->string('role')->toString(),
        ]);

        $member = $workspace->users()
            ->withPivot('role')
            ->findOrFail($user->id);

        return new WorkspaceMemberResource($member)->response();
    }

    public function destroy(
        Request $request,
        Workspace $workspace,
        User $user,
        RemoveWorkspaceMember $removeWorkspaceMember,
    ): JsonResponse {
        Gate::authorize('manageMembers', $workspace);

        $member = $workspace->users()
            ->withPivot('role')
            ->whereKey($user->id)
            ->firstOrFail();

        if ($member->pivot->role === WorkspaceRole::Owner->value) {
            abort(422, 'Workspace owner cannot be removed.');
        }

        if (
            $workspace->hasRole($request->user(), WorkspaceRole::Admin)
            && $member->pivot->role !== WorkspaceRole::Member->value
        ) {
            abort(403);
        }

        $removeWorkspaceMember->handle($workspace, $user);

        return response()->json(null, 204);
    }
}
