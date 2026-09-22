<?php

namespace App\Queries\Issues;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Pagination\LengthAwarePaginator;

class IssueIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Issue>
     */
    public function paginate(Project $project, array $filters): LengthAwarePaginator
    {
        $query = $project->issues()
            ->with(['project', 'reporter', 'assignee', 'labels']);

        if (isset($filters['status'])) {
            $query->status(IssueStatus::from($filters['status']));
        }

        if (isset($filters['priority'])) {
            $query->priority(IssuePriority::from($filters['priority']));
        }

        if (isset($filters['assignee_id'])) {
            $query->assignedTo($filters['assignee_id']);
        }

        if (isset($filters['reporter_id'])) {
            $query->reportedBy($filters['reporter_id']);
        }

        if (isset($filters['label_ids'])) {
            $query->labeledWith($filters['label_ids']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->orderBy('issues.id', $sortDirection)
            ->paginate($filters['per_page'] ?? 20);
    }
}
