<?php

namespace App\Actions\Issues;

use App\Models\Issue;
use App\Notifications\IssueAssigned;

class UpdateIssue
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Issue $issue, array $data): Issue
    {
        $issue->update($data);

        if ($issue->wasChanged('assignee_id') && $issue->assignee_id !== null) {
            $issue->assignee()->first()?->notify(new IssueAssigned($issue));
        }

        return $issue;
    }
}
