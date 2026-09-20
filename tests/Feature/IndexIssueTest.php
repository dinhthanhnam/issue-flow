<?php

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

test('issues can be filtered by status', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->users()->attach($user);

    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
    ]);

    Issue::factory()->create([
        'project_id' => $project->id,
        'reporter_id' => $user->id,
        'status' => IssueStatus::Todo,
    ]);

    Issue::factory()->create([
        'project_id' => $project->id,
        'reporter_id' => $user->id,
        'status' => IssueStatus::Done,
    ]);

    $this
        ->actingAs($user)
        ->getJson(route('projects.issues.index', [
            'project' => $project,
            'status' => 'todo',
        ]))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'todo');
});
