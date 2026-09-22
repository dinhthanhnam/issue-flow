<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('a project participant can list project members', function () {
    $viewer = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach([$viewer->id, $member->id], [
        'role' => WorkspaceRole::Member->value,
    ]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($viewer, ['role' => ProjectRole::Viewer->value]);
    $project->users()->attach($member, ['role' => ProjectRole::Member->value]);

    actingAs($viewer)
        ->getJson(route('projects.members.index', $project))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'id' => $member->id,
            'role' => ProjectRole::Member->value,
        ]);
});

test('a workspace manager can add a project lead', function (WorkspaceRole $workspaceRole) {
    $actor = User::factory()->create();
    $target = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($actor, ['role' => $workspaceRole->value]);
    $workspace->users()->attach($target, ['role' => WorkspaceRole::Member->value]);
    $project = Project::factory()->for($workspace)->create();

    actingAs($actor)
        ->postJson(route('projects.members.store', $project), [
            'user_id' => $target->id,
            'role' => ProjectRole::Lead->value,
        ])
        ->assertCreated()
        ->assertJsonPath('data.id', $target->id)
        ->assertJsonPath('data.role', ProjectRole::Lead->value);

    assertDatabaseHas('project_user', [
        'project_id' => $project->id,
        'user_id' => $target->id,
        'role' => ProjectRole::Lead->value,
    ]);
})->with([
    'workspace owner' => WorkspaceRole::Owner,
    'workspace admin' => WorkspaceRole::Admin,
]);

test('a project lead can add members and viewers', function (ProjectRole $role) {
    $lead = User::factory()->create();
    $target = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach([$lead->id, $target->id], [
        'role' => WorkspaceRole::Member->value,
    ]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($lead, ['role' => ProjectRole::Lead->value]);

    actingAs($lead)
        ->postJson(route('projects.members.store', $project), [
            'user_id' => $target->id,
            'role' => $role->value,
        ])
        ->assertCreated();

    assertDatabaseHas('project_user', [
        'project_id' => $project->id,
        'user_id' => $target->id,
        'role' => $role->value,
    ]);
})->with([
    'project member' => ProjectRole::Member,
    'project viewer' => ProjectRole::Viewer,
]);

test('a project lead cannot add another lead', function () {
    $lead = User::factory()->create();
    $target = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach([$lead->id, $target->id], [
        'role' => WorkspaceRole::Member->value,
    ]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($lead, ['role' => ProjectRole::Lead->value]);

    actingAs($lead)
        ->postJson(route('projects.members.store', $project), [
            'user_id' => $target->id,
            'role' => ProjectRole::Lead->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('role');

    assertDatabaseMissing('project_user', [
        'project_id' => $project->id,
        'user_id' => $target->id,
    ]);
});

test('a user outside the workspace cannot be added to a project', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $project = Project::factory()->for($workspace)->create();

    actingAs($owner)
        ->postJson(route('projects.members.store', $project), [
            'user_id' => $outsider->id,
            'role' => ProjectRole::Member->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user_id');
});

test('a project lead can change a member between member and viewer roles', function () {
    $lead = User::factory()->create();
    $target = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach([$lead->id, $target->id], [
        'role' => WorkspaceRole::Member->value,
    ]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($lead, ['role' => ProjectRole::Lead->value]);
    $project->users()->attach($target, ['role' => ProjectRole::Member->value]);

    actingAs($lead)
        ->patchJson(route('projects.members.update', [$project, $target]), [
            'role' => ProjectRole::Viewer->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.role', ProjectRole::Viewer->value);

    assertDatabaseHas('project_user', [
        'project_id' => $project->id,
        'user_id' => $target->id,
        'role' => ProjectRole::Viewer->value,
    ]);
});

test('a project lead cannot modify another lead', function () {
    $actor = User::factory()->create();
    $target = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach([$actor->id, $target->id], [
        'role' => WorkspaceRole::Member->value,
    ]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($actor, ['role' => ProjectRole::Lead->value]);
    $project->users()->attach($target, ['role' => ProjectRole::Lead->value]);

    actingAs($actor)
        ->patchJson(route('projects.members.update', [$project, $target]), [
            'role' => ProjectRole::Member->value,
        ])
        ->assertForbidden();

    assertDatabaseHas('project_user', [
        'project_id' => $project->id,
        'user_id' => $target->id,
        'role' => ProjectRole::Lead->value,
    ]);
});

test('a project lead cannot remove another lead', function () {
    $actor = User::factory()->create();
    $target = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach([$actor->id, $target->id], [
        'role' => WorkspaceRole::Member->value,
    ]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($actor, ['role' => ProjectRole::Lead->value]);
    $project->users()->attach($target, ['role' => ProjectRole::Lead->value]);

    actingAs($actor)
        ->deleteJson(route('projects.members.destroy', [$project, $target]))
        ->assertForbidden();

    assertDatabaseHas('project_user', [
        'project_id' => $project->id,
        'user_id' => $target->id,
        'role' => ProjectRole::Lead->value,
    ]);
});

test('a project member cannot manage project membership', function () {
    $actor = User::factory()->create();
    $target = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach([$actor->id, $target->id], [
        'role' => WorkspaceRole::Member->value,
    ]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($actor, ['role' => ProjectRole::Member->value]);

    actingAs($actor)
        ->postJson(route('projects.members.store', $project), [
            'user_id' => $target->id,
            'role' => ProjectRole::Viewer->value,
        ])
        ->assertForbidden();
});

test('a workspace manager can remove a project lead', function () {
    $owner = User::factory()->create();
    $lead = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $workspace->users()->attach($lead, ['role' => WorkspaceRole::Member->value]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($lead, ['role' => ProjectRole::Lead->value]);

    actingAs($owner)
        ->deleteJson(route('projects.members.destroy', [$project, $lead]))
        ->assertNoContent();

    assertDatabaseMissing('project_user', [
        'project_id' => $project->id,
        'user_id' => $lead->id,
    ]);
});
