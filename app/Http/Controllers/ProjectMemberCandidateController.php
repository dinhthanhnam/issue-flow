<?php

namespace App\Http\Controllers;

use App\Http\Requests\FindProjectMemberCandidateRequest;
use App\Http\Resources\MemberCandidateResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class ProjectMemberCandidateController extends Controller
{
    public function show(
        FindProjectMemberCandidateRequest $request,
        Project $project,
    ): JsonResponse {
        $candidate = $project->workspace()
            ->firstOrFail()
            ->users()
            ->where('users.email', $request->validated('email'))
            ->whereDoesntHave(
                'projects',
                fn ($query) => $query->whereKey($project->id),
            )
            ->firstOrFail();

        return new MemberCandidateResource($candidate)->response();
    }
}
