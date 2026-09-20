<?php

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

test('a workspace member can update an issue', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->users()->attach($user, [
        'role' => 'member',
    ]);

    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
    ]);

    $issue = Issue::factory()->create([
        'project_id' => $project->id,
        'reporter_id' => $user->id,
        'status' => IssueStatus::Todo,
        'priority' => IssuePriority::Medium,
    ]);

    $this
        ->actingAs($user)
        ->patchJson(route('issues.update', $issue), [
            'status' => 'in_progress',
            'priority' => 'high',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'in_progress')
        ->assertJsonPath('data.priority', 'high');

    $this->assertDatabaseHas('issues', [
        'id' => $issue->id,
        'status' => 'in_progress',
        'priority' => 'high',
    ]);
});
