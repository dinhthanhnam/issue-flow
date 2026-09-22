<?php

namespace App\Actions\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class RemoveWorkspaceMember
{
    public function handle(Workspace $workspace, User $member): void
    {
        DB::transaction(function () use ($member, $workspace): void {
            DB::table('project_user')
                ->where('user_id', $member->id)
                ->whereIn('project_id', $workspace->projects()->select('id'))
                ->delete();

            $workspace->users()->detach($member);
        });
    }
}
