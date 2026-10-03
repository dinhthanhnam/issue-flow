<?php

namespace App\Http\Controllers;

use App\Http\Requests\FindWorkspaceMemberCandidateRequest;
use App\Http\Resources\MemberCandidateResource;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class WorkspaceMemberCandidateController extends Controller
{
    public function show(
        FindWorkspaceMemberCandidateRequest $request,
        Workspace $workspace,
    ): JsonResponse {
        $candidate = User::query()
            ->where('email', $request->validated('email'))
            ->whereDoesntHave(
                'workspaces',
                fn ($query) => $query->whereKey($workspace->id),
            )
            ->firstOrFail();

        return new MemberCandidateResource($candidate)->response();
    }
}
