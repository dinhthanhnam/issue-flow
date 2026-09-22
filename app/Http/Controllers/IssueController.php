<?php

namespace App\Http\Controllers;

use App\Actions\Issues\CreateIssue;
use App\Actions\Issues\UpdateIssue;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Http\Requests\IndexIssueRequest;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class IssueController extends Controller
{
    public function index(IndexIssueRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('viewIssues', $project);

        $data = $request->validated();

        $issues = $project->issues()
            ->with(['project', 'reporter', 'assignee'])
            ->when(
                isset($data['status']),
                fn (Builder $query) => $query->status(IssueStatus::from($data['status'])),
            )
            ->when(
                isset($data['assignee_id']),
                fn (Builder $query) => $query->assignedTo($data['assignee_id']),
            )
            ->latest()
            ->paginate(20);

        return IssueResource::collection($issues)->response();
    }

    public function store(
        StoreIssueRequest $request,
        Project $project,
        CreateIssue $createIssue,
    ): JsonResponse {
        $data = $request->validated();

        $issue = $createIssue->handle(
            project: $project,
            reporter: $request->user(),
            title: $data['title'],
            description: $data['description'] ?? null,
            assignee: isset($data['assignee_id']) ? User::query()->findOrFail($data['assignee_id']) : null,
            priority: isset($data['priority']) ? IssuePriority::from($data['priority']) : IssuePriority::Medium,
        );

        $issue->load(['project', 'reporter', 'assignee']);

        return new IssueResource($issue)
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateIssueRequest $request,
        Issue $issue,
        UpdateIssue $updateIssue,
    ): JsonResponse {
        $issue = $updateIssue->handle(
            issue: $issue,
            user: $request->user(),
            data: $request->validated(),
        );

        $issue->load(['project', 'reporter', 'assignee']);

        return new IssueResource($issue)->response();
    }
}
