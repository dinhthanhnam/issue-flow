<?php

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

test('issue can be created when workspace is active', function () {
    $user = User::factory()->create();

    $workspace = Workspace::factory()->create([
        'archived_at' => null,
    ]);

    $workspace->users()->attach($user, [
        'role' => 'member',
    ]);

    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
    ]);
    $project->users()->attach($user, ['role' => ProjectRole::Member->value]);

    $this
        ->actingAs($user)
        ->postJson(route('projects.issues.store', $project), [
            'title' => 'Active workspace issue',
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('issues', [
        'project_id' => $project->id,
        'title' => 'Active workspace issue',
    ]);
});

test('issue cannot be created when workspace is archived', function () {
    $user = User::factory()->create();

    $workspace = Workspace::factory()->create([
        'archived_at' => now(),
    ]);

    $workspace->users()->attach($user, [
        'role' => 'member',
    ]);

    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
    ]);
    $project->users()->attach($user, ['role' => ProjectRole::Member->value]);

    $this
        ->actingAs($user)
        ->postJson(route('projects.issues.store', $project), [
            'title' => 'Should not be created',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('issues', [
        'project_id' => $project->id,
        'title' => 'Should not be created',
    ]);
});
