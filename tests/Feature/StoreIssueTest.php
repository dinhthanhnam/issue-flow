<?php

use App\Enums\IssuePriority;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

test('A user can create an issue', function () {
    $user = User::factory()->create();

    $workspace = Workspace::factory()->create();

    $workspace->users()->attach($user, [
        'role' => 'owner'
    ]);

    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'key' => 'TEST',
    ]);

    $response = $this
        ->actingAs($user)
        ->postJson(route('projects.issues.store', $project), [
            'title' => 'Implement authentication',
            'description' => 'Add login flow',
            'assignee_id' => $user->id,
            'priority' => IssuePriority::High->value,
        ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.number', 1)
        ->assertJsonPath('data.title', 'Implement authentication')
        ->assertJsonPath('data.status', 'todo')
        ->assertJsonPath('data.priority', 'high');

    $this->assertDatabaseHas('issues', [
        'project_id' => $project->id,
        'reporter_id' => $user->id,
        'assignee_id' => $user->id,
        'number' => 1,
        'title' => 'Implement authentication',
        'status' => 'todo',
        'priority' => 'high',
    ]);
});

test('an issue cannot be assigned to a user outside the workspace', function () {
    $reporter = User::factory()->create();
    $outsider = User::factory()->create();

    $workspace = Workspace::factory()->create();

    $workspace->users()->attach($reporter, [
        'role' => 'owner',
    ]);

    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
    ]);

    $response = $this
        ->actingAs($reporter)
        ->postJson(route('projects.issues.store', $project), [
            'title' => 'Secret issue',
            'assignee_id' => $outsider->id,
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('assignee_id');

    $this->assertDatabaseCount('issues', 0);
});

test('a user outside the workspace cannot create an issue', function () {
    $member = User::factory()->create();
    $outsider = User::factory()->create();

    $workspace = Workspace::factory()->create();

    $workspace->users()->attach($member, [
        'role' => 'owner',
    ]);

    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
    ]);

    $response = $this
        ->actingAs($outsider)
        ->postJson(route('projects.issues.store', $project), [
            'title' => 'Unauthorized issue',
        ]);

    $response->assertForbidden();

    $this->assertDatabaseCount('issues', 0);
});

test('a workspace member can list project issues', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->users()->attach($user, [
        'role' => 'member',
    ]);

    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
    ]);

    Issue::factory()
        ->count(3)
        ->create([
            'project_id' => $project->id,
            'reporter_id' => $user->id,
        ]);

    $response = $this
        ->actingAs($user)
        ->getJson(route('projects.issues.index', $project));

    $response
        ->assertSuccessful()
        ->assertJsonCount(3, 'data');
});
