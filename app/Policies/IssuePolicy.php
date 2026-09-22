<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\Label;
use App\Models\User;

class IssuePolicy
{
    public function view(User $user, Issue $issue): bool
    {
        return $issue->project->isAccessibleBy($user);
    }

    public function update(User $user, Issue $issue): bool
    {
        return $issue->project->canContribute($user);
    }

    public function comment(User $user, Issue $issue): bool
    {
        return $issue->project->canContribute($user);
    }

    public function manageLabel(User $user, Issue $issue, Label $label): bool
    {
        return $issue->project->canContribute($user)
            && $issue->project->workspace_id === $label->workspace_id;
    }
}
