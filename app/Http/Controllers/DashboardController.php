<?php

namespace App\Http\Controllers;

use App\Http\Resources\IssueResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\WorkspaceResource;
use App\Queries\Issues\IssueIndexQuery;
use App\Queries\Projects\ProjectIndexQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        ProjectIndexQuery $projectIndexQuery,
        IssueIndexQuery $issueIndexQuery,
    ): Response {
        $user = $request->user();
        $workspaces = $user->workspaces()
            ->withPivot('role')
            ->latest('workspaces.created_at')
            ->get();

        $workspace = $workspaces->firstWhere('id', $request->integer('workspace'))
            ?? $workspaces->first();
        $projects = $workspace === null
            ? collect()
            : $projectIndexQuery->get($workspace, $user);
        $project = $projects->firstWhere('id', $request->integer('project'))
            ?? $projects->first();
        $issues = $project === null
            ? null
            : $issueIndexQuery->paginate($project, ['per_page' => 100]);

        return Inertia::render('dashboard', [
            'workspaces' => WorkspaceResource::collection($workspaces)->resolve($request),
            'workspace' => $workspace === null
                ? null
                : new WorkspaceResource($workspace)->resolve($request),
            'projects' => ProjectResource::collection($projects)->resolve($request),
            'project' => $project === null
                ? null
                : new ProjectResource($project)->resolve($request),
            'issues' => $issues === null
                ? null
                : IssueResource::collection($issues)->response()->getData(true),
        ]);
    }
}
