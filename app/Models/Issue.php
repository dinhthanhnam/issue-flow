<?php

namespace App\Models;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'reporter_id', 'assignee_id', 'number', 'title', 'description', 'status', 'priority'])]
class Issue extends Model
{
    /** @use HasFactory<IssueFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => IssueStatus::class,
            'priority' => IssuePriority::class,
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<Comment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** @return BelongsToMany<Label, $this> */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }

    /** @return HasMany<IssueActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(IssueActivity::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** @param Builder<Issue> $query */
    #[Scope]
    protected function status(Builder $query, IssueStatus $status): void
    {
        $query->where('status', $status);
    }

    /** @param Builder<Issue> $query */
    #[Scope]
    protected function assignedTo(Builder $query, int $userId): void
    {
        $query->where('assignee_id', $userId);
    }

    /** @param Builder<Issue> $query */
    #[Scope]
    protected function priority(Builder $query, IssuePriority $priority): void
    {
        $query->where('priority', $priority);
    }

    /** @param Builder<Issue> $query */
    #[Scope]
    protected function reportedBy(Builder $query, int $userId): void
    {
        $query->where('reporter_id', $userId);
    }

    /**
     * @param  Builder<Issue>  $query
     * @param  array<int, int>  $labelIds
     */
    #[Scope]
    protected function labeledWith(Builder $query, array $labelIds): void
    {
        $query->whereHas(
            'labels',
            fn (Builder $labels) => $labels->whereKey($labelIds),
        );
    }

    /** @param Builder<Issue> $query */
    #[Scope]
    protected function search(Builder $query, string $search): void
    {
        $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
