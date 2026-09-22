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
use Illuminate\Http\JsonResponse;

class IssueController extends Controller
{
    public function index(IndexIssueRequest $request, Project $project): JsonResponse
    {
        $data = $request->validated();

        $query = $project->issues()
            ->with(['project', 'reporter', 'assignee', 'labels']);

        if (isset($data['status'])) {
            $query->status(IssueStatus::from($data['status']));
        }

        if (isset($data['priority'])) {
            $query->priority(IssuePriority::from($data['priority']));
        }

        if (isset($data['assignee_id'])) {
            $query->assignedTo($data['assignee_id']);
        }

        if (isset($data['reporter_id'])) {
            $query->reportedBy($data['reporter_id']);
        }

        if (isset($data['label_ids'])) {
            $query->labeledWith($data['label_ids']);
        }

        if (isset($data['search'])) {
            $query->search($data['search']);
        }

        $sortBy = $data['sort_by'] ?? 'created_at';
        $sortDirection = $data['sort_direction'] ?? 'desc';

        $issues = $query
            ->orderBy($sortBy, $sortDirection)
            ->orderBy('issues.id', $sortDirection)
            ->paginate($data['per_page'] ?? 20)
            ->withQueryString();

        return IssueResource::collection($issues)->response();
    }

    public function store(
        StoreIssueRequest $request,
        Project $project,
        CreateIssue $createIssue,
    ): JsonResponse {
        $data = $request->validated();

        $assignee = isset($data['assignee_id'])
            ? User::query()->whereKey($data['assignee_id'])->firstOrFail()
            : null;

        $issue = $createIssue->handle(
            project: $project,
            reporter: $request->user(),
            title: $data['title'],
            description: $data['description'] ?? null,
            assignee: $assignee,
            priority: isset($data['priority']) ? IssuePriority::from($data['priority']) : IssuePriority::Medium,
        );

        $issue->load(['project', 'reporter', 'assignee', 'labels']);

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

        $issue->load(['project', 'reporter', 'assignee', 'labels']);

        return new IssueResource($issue)->response();
    }
}
