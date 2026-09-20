<?php

namespace App\Listeners;

use App\Events\IssueUpdated;

class RecordIssueActivity
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
        $oldValues = [];
        $newValues = [];

        foreach ($event->changes as $field => $change) {
            $oldValues[$field] = $change['old'];
            $newValues[$field] = $change['new'];
        }

        $event->issue->activities()->create([
            'user_id' => $event->user->id,
            'type' => 'issue_updated',
            'old_value' => $oldValues,
            'new_value' => $newValues,
        ]);
    }
}
