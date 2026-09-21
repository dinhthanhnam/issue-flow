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

        if ($change === null || $change['new'] === null) {
            return;
        }

        $assignee = User::query()->find($change['new']);

        $assignee?->notify(
            new IssueAssigned($event->issue)
        );
    }
}
