<?php

namespace App\Models;

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Workspace $workspace
 */
#[Fillable(['workspace_id', 'name', 'key', 'description'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<Issue, $this> */
    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function hasParticipant(User $user): bool
    {
        return $this->projectRoleFor($user) !== null;
    }

    public function hasProjectRole(User $user, ProjectRole $role): bool
    {
        return $this->projectRoleFor($user) === $role;
    }

    /**
     * @param  array<int, ProjectRole>  $roles
     */
    public function hasAnyProjectRole(User $user, array $roles): bool
    {
        return in_array($this->projectRoleFor($user), $roles, true);
    }

    public function projectRoleFor(User $user): ?ProjectRole
    {
        $role = $this->users()
            ->whereKey($user->id)
            ->value('project_user.role');

        return $role === null ? null : ProjectRole::from($role);
    }

    public function isWorkspaceManager(User $user): bool
    {
        return $this->workspace->hasAnyRole($user, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
        ]);
    }

    public function isAccessibleBy(User $user): bool
    {
        return $this->isWorkspaceManager($user)
            || ($this->workspace->hasMember($user) && $this->hasParticipant($user));
    }

    public function canContribute(User $user): bool
    {
        return $this->isWorkspaceManager($user)
            || (
                $this->workspace->hasMember($user)
                && $this->hasAnyProjectRole($user, [ProjectRole::Lead, ProjectRole::Member])
            );
    }

    public function canManageMembers(User $user): bool
    {
        return $this->isWorkspaceManager($user)
            || (
                $this->workspace->hasMember($user)
                && $this->hasProjectRole($user, ProjectRole::Lead)
            );
    }
}
