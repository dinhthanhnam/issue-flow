<?php

namespace App\Actions\Workspaces;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveWorkspaceMember
{
    public function handle(Workspace $workspace, User $member): void
    {
        $role = $workspace->roleFor($member);

        if ($role === null) {
            throw (new ModelNotFoundException)->setModel(User::class, [$member->id]);
        }

        if ($role === WorkspaceRole::Owner) {
            throw ValidationException::withMessages([
                'user' => 'Workspace owner cannot be removed.',
            ]);
        }

        DB::transaction(function () use ($member, $workspace): void {
            DB::table('project_user')
                ->where('user_id', $member->id)
                ->whereIn('project_id', $workspace->projects()->select('id'))
                ->delete();

            $workspace->users()->detach($member);
        });
    }
}
