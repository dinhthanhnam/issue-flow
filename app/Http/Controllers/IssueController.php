<?php

namespace App\Http\Controllers;

use App\Actions\Issues\CreateIssue;
use App\Enums\IssuePriority;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Resources\IssueResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IssueController extends Controller
{
    public function index(Project $project): AnonymousResourceCollection
    {
        $issues = $project->issues()
            ->latest()
            ->get();

        return IssueResource::collection($issues);
    }

    public function store(
        StoreIssueRequest $request,
        Project $project,
        CreateIssue $createIssue,
    ): IssueResource {
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
        return new IssueResource($issue);
    }
}
