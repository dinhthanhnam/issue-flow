<?php

namespace App\Models;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'reporter_id', 'assignee_id', 'number', 'title', 'description', 'status', 'priority'])]
class Issue extends Model
{
    /** @use HasFactory<\Database\Factories\IssueFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => IssueStatus::class,
            'priority' => IssuePriority::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('status', '!=', IssueStatus::Done);
    }

    #[Scope]
    protected function status(Builder $query, IssueStatus $status): void
    {
        $query->where('status', $status);
    }

    #[Scope]
    protected function assignedTo(Builder $query, int $userId): void
    {
        $query->where('assignee_id', $userId);
    }
}
