<?php

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

test('a workspace member can comment on an issue', function () {
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
    ]);

    $this
        ->actingAs($user)
        ->postJson(route('issues.comments.store', $issue), [
            'body' => 'I am working on this issue.',
        ])
        ->assertSuccessful()
        ->assertJsonPath(
            'data.body',
            'I am working on this issue.',
        )
        ->assertJsonPath('data.user.id', $user->id);

    $this->assertDatabaseHas('comments', [
        'issue_id' => $issue->id,
        'user_id' => $user->id,
        'body' => 'I am working on this issue.',
    ]);
});

test('a user outside the workspace cannot comment', function () {
    $member = User::factory()->create();
    $outsider = User::factory()->create();

    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($member);

    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
    ]);

    $issue = Issue::factory()->create([
        'project_id' => $project->id,
        'reporter_id' => $member->id,
    ]);

    $this
        ->actingAs($outsider)
        ->postJson(route('issues.comments.store', $issue), [
            'body' => 'Hello',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('comments', [
        'issue_id' => $issue->id,
        'user_id' => $outsider->id,
    ]);
});
