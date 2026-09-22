<?php

namespace App\Http\Controllers;

use App\Actions\Workspaces\RemoveWorkspaceMember;
use App\Enums\WorkspaceRole;
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
        $currentRole = $workspace->users()
            ->whereKey($user->id)
            ->value('user_workspace.role');

        abort_if($currentRole === null, 404);

        if ($currentRole === WorkspaceRole::Owner->value) {
            abort(422, 'Owner role cannot be changed through this endpoint.');
        }

        $workspace->users()->updateExistingPivot($user->id, [
            'role' => $request->validated('role'),
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

        $memberRole = $workspace->users()
            ->whereKey($user->id)
            ->value('user_workspace.role');

        abort_if($memberRole === null, 404);

        if ($memberRole === WorkspaceRole::Owner->value) {
            abort(422, 'Workspace owner cannot be removed.');
        }

        if (
            $workspace->hasRole($request->user(), WorkspaceRole::Admin)
            && $memberRole !== WorkspaceRole::Member->value
        ) {
            abort(403);
        }

        $removeWorkspaceMember->handle($workspace, $user);

        return response()->json(status: 204);
    }
}
