<?php

namespace App\Actions\Issues;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateIssue
{
    public function handle(
        Project $project,
        User $reporter,
        string $title,
        ?string $description = null,
        ?User $assignee = null,
        IssuePriority $priority = IssuePriority::Medium,
    ): Issue {
        return DB::transaction(function () use (
            $project,
            $reporter,
            $title,
            $description,
            $assignee,
            $priority
        ) {
            $lockedProject = Project::query()
                ->lockForUpdate()
                ->findOrFail($project->id);

            $nextIssueNumber = $lockedProject->issues()->max('number') + 1;

            return $lockedProject->issues()->create([
                'reporter_id' => $reporter->id,
                'assignee_id' => $assignee?->id,
                'number' => $nextIssueNumber,
                'title' => $title,
                'description' => $description,
                'priority' => $priority,
                'status' => IssueStatus::Todo,
            ]);
        });
    }
}
