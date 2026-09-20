<?php

namespace App\Listeners;

use App\Events\IssueUpdated;
use App\Models\User;
use App\Notifications\IssueAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyIssueAssignee implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(IssueUpdated $event): void
    {
        $change = $event->changes['assignee_id'] ?? null;

        if ($change === null) {
            return;
        }

        $newAssigneeId = $change['new'];

        if ($newAssigneeId === null) {
            return;
        }

        $assignee = User::query()->find($newAssigneeId);

        $assignee?->notify(
            new IssueAssigned($event->issue)
        );
    }
}
