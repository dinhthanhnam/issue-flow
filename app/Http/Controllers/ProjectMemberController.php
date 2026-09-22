<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectMemberRequest;
use App\Http\Requests\UpdateProjectMemberRequest;
use App\Http\Resources\ProjectMemberResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ProjectMemberController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $members = $project->users()
            ->orderBy('users.name')
            ->get();

        return ProjectMemberResource::collection($members)->response();
    }

    public function store(
        StoreProjectMemberRequest $request,
        Project $project,
    ): JsonResponse {
        $data = $request->validated();

        $project->users()->attach($data['user_id'], [
            'role' => $data['role'],
        ]);

        $member = $project->users()->findOrFail($data['user_id']);

        return new ProjectMemberResource($member)
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateProjectMemberRequest $request,
        Project $project,
        User $user,
    ): JsonResponse {
        abort_unless($project->hasParticipant($user), 404);

        $project->users()->updateExistingPivot($user->id, [
            'role' => $request->validated('role'),
        ]);

        $member = $project->users()->findOrFail($user->id);

        return new ProjectMemberResource($member)->response();
    }

    public function destroy(Project $project, User $user): JsonResponse
    {
        abort_unless($project->hasParticipant($user), 404);

        Gate::authorize('manageMember', [$project, $user]);

        $project->users()->detach($user);

        return response()->json(status: 204);
    }
}
