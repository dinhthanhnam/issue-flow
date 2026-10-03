<?php

namespace App\Http\Controllers;

use App\Actions\Issues\CreateIssue;
use App\Actions\Issues\UpdateIssue;
use App\Enums\IssuePriority;
use App\Http\Requests\IndexIssueRequest;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Queries\Issues\IssueIndexQuery;
use Illuminate\Http\JsonResponse;

class IssueController extends Controller
{
    public function index(
        IndexIssueRequest $request,
        Project $project,
        IssueIndexQuery $issueIndexQuery,
    ): JsonResponse {
        $issues = $issueIndexQuery
            ->paginate($project, $request->validated())
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
        $updateIssue->handle($issue, $request->validated());

        $issue->load(['project', 'reporter', 'assignee', 'labels']);

        return new IssueResource($issue)->response();
    }
}
