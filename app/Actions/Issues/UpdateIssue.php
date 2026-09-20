<?php

namespace App\Actions\Issues;

use App\Events\IssueUpdated;
use App\Models\Issue;
use App\Models\User;

class UpdateIssue
{
    public function handle(
        Issue $issue,
        User $user,
        array $data,
    ): Issue {
        $original = $issue->getOriginal();

        $issue->update($data);

        $changes = [];

        foreach ($issue->getChanges() as $field => $newValue) {
            if ($field === 'updated_at') {
                continue;
            }

            $changes[$field] = [
                'old' => $original[$field] ?? null,
                'new' => $newValue,
            ];
        }

        if ($changes !== []) {
            IssueUpdated::dispatch(
                $issue,
                $user,
                $changes,
            );
        }

        return $issue;
    }
}
