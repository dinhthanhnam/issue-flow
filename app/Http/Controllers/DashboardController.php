<?php

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Http\Resources\IssueResource;
use App\Http\Resources\LabelResource;
use App\Http\Resources\ProjectMemberResource;
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
        $labels = $workspace === null
            ? collect()
            : $workspace->labels()->orderBy('name')->get();
        $project = $projects->firstWhere('id', $request->integer('project'))
            ?? $projects->first();
        $assignees = $project === null
            ? collect()
            : $project->users()
                ->whereIn('project_user.role', [
                    ProjectRole::Lead->value,
                    ProjectRole::Member->value,
                ])
                ->orderBy('users.name')
                ->get();
        $issues = $project === null
            ? null
            : $issueIndexQuery->paginate($project, ['per_page' => 100]);

        return Inertia::render('dashboard', [
            'workspaces' => WorkspaceResource::collection($workspaces)->resolve($request),
            'workspace' => $workspace === null
                ? null
                : new WorkspaceResource($workspace)->resolve($request),
            'projects' => ProjectResource::collection($projects)->resolve($request),
            'labels' => LabelResource::collection($labels)->resolve($request),
            'project' => $project === null
                ? null
                : new ProjectResource($project)->resolve($request),
            'assignees' => ProjectMemberResource::collection($assignees)->resolve($request),
            'issues' => $issues === null
                ? null
                : IssueResource::collection($issues)->response()->getData(true),
        ]);
    }
}
