<?php

use App\Enums\ProjectRole;
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
    $project->users()->attach($user, ['role' => ProjectRole::Member->value]);

    $issue = Issue::factory()->create([
        'project_id' => $project->id,
        'reporter_id' => $user->id,
    ]);

    $this
        ->actingAs($user)
        ->postJson(route('issues.comments.store', $issue), [
            'body' => 'I am working on this issue.',
        ])
        ->assertCreated()
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

test('a project viewer cannot comment on an issue', function () {
    $viewer = User::factory()->create();
    $reporter = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach([$viewer->id, $reporter->id], [
        'role' => 'member',
    ]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($viewer, ['role' => ProjectRole::Viewer->value]);
    $issue = Issue::factory()->for($project)->create([
        'reporter_id' => $reporter->id,
    ]);

    $this->actingAs($viewer)
        ->postJson(route('issues.comments.store', $issue), [
            'body' => 'Viewer comment',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('comments', [
        'issue_id' => $issue->id,
        'user_id' => $viewer->id,
    ]);
});
