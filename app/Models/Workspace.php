<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Project> $projects
 * @property-read Collection<int, Label> $labels
 */
#[Fillable(['name', 'slug'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /** @return HasMany<Label, $this> */
    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    /** @param Builder<Workspace> $query */
    #[Scope]
    public function archived_at(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    public function hasMember(User $user): bool
    {
        return $this->roleFor($user) !== null;
    }

    public function hasRole(User $user, WorkspaceRole $role): bool
    {
        return $this->roleFor($user) === $role;
    }

    /**
     * @param  array<int, WorkspaceRole>  $roles
     */
    public function hasAnyRole(User $user, array $roles): bool
    {
        return in_array($this->roleFor($user), $roles, true);
    }

    public function roleFor(User $user): ?WorkspaceRole
    {
        $role = $this->users()
            ->whereKey($user->id)
            ->value('user_workspace.role');

        return $role === null ? null : WorkspaceRole::from($role);
    }
}
