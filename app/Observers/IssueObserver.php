<?php

namespace App\Observers;

use App\Models\Issue;
use App\Notifications\IssueAssigned;

class IssueObserver
{
    public function updated(Issue $issue): void
    {
        if (! $issue->wasChanged('assignee_id') || $issue->assignee_id === null) {
            return;
        }

        $issue->assignee()->first()?->notify(new IssueAssigned($issue));
    }
}
