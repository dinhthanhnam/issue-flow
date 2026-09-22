<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('a workspace member can list only projects in that workspace', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $otherWorkspace = Workspace::factory()->create();

    $workspace->users()->attach($user, [
        'role' => WorkspaceRole::Member->value,
    ]);

    $project = Project::factory()->for($workspace)->create();
    $unassignedProject = Project::factory()->for($workspace)->create();
    $otherProject = Project::factory()->for($otherWorkspace)->create();
    $project->users()->attach($user, [
        'role' => ProjectRole::Member->value,
    ]);

    actingAs($user)
        ->getJson(route('workspaces.projects.index', $workspace))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $project->id,
        ])
        ->assertJsonMissing([
            'id' => $unassignedProject->id,
        ])
        ->assertJsonMissing([
            'id' => $otherProject->id,
        ]);
});

test('a non-member cannot list workspace projects', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();

    actingAs($user)
        ->getJson(route('workspaces.projects.index', $workspace))
        ->assertForbidden();
});

test('a workspace member can view a project', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $project = Project::factory()->for($workspace)->create();

    $workspace->users()->attach($user, [
        'role' => WorkspaceRole::Member->value,
    ]);
    $project->users()->attach($user, [
        'role' => ProjectRole::Viewer->value,
    ]);

    actingAs($user)
        ->getJson(route('projects.show', $project))
        ->assertOk()
        ->assertJsonFragment([
            'id' => $project->id,
        ]);
});

test('a non-member cannot view a project', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $project = Project::factory()->for($workspace)->create();

    actingAs($user)
        ->getJson(route('projects.show', $project))
        ->assertForbidden();
});

test('an unauthenticated user cannot access projects', function () {
    $workspace = Workspace::factory()->create();

    $this->getJson(route('workspaces.projects.index', $workspace))
        ->assertUnauthorized();
});

test('an owner or admin can create a project with an uppercase key', function (WorkspaceRole $role) {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user, ['role' => $role->value]);

    $response = actingAs($user)
        ->postJson(route('workspaces.projects.store', $workspace), [
            'name' => 'Backend Platform',
            'key' => 'back',
            'description' => 'Backend work',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.workspace_id', $workspace->id)
        ->assertJsonPath('data.name', 'Backend Platform')
        ->assertJsonPath('data.key', 'BACK');

    assertDatabaseHas('projects', [
        'workspace_id' => $workspace->id,
        'name' => 'Backend Platform',
        'key' => 'BACK',
        'description' => 'Backend work',
    ]);

    $project = Project::query()
        ->whereBelongsTo($workspace)
        ->where('key', 'BACK')
        ->sole();

    assertDatabaseHas('project_user', [
        'project_id' => $project->id,
        'user_id' => $user->id,
        'role' => ProjectRole::Lead->value,
    ]);
})->with([
    'owner' => WorkspaceRole::Owner,
    'admin' => WorkspaceRole::Admin,
]);

test('a member cannot create a project', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user, [
        'role' => WorkspaceRole::Member->value,
    ]);

    actingAs($user)
        ->postJson(route('workspaces.projects.store', $workspace), [
            'name' => 'Forbidden Project',
            'key' => 'NOPE',
        ])
        ->assertForbidden();

    assertDatabaseMissing('projects', [
        'workspace_id' => $workspace->id,
        'key' => 'NOPE',
    ]);
});

test('a project key must be unique within its workspace', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user, [
        'role' => WorkspaceRole::Owner->value,
    ]);
    Project::factory()->for($workspace)->create(['key' => 'API']);

    actingAs($user)
        ->postJson(route('workspaces.projects.store', $workspace), [
            'name' => 'Duplicate Project',
            'key' => 'api',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('key');

    expect(Project::query()
        ->whereBelongsTo($workspace)
        ->where('key', 'API')
        ->count())->toBe(1);
});

test('different workspaces may use the same project key', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $otherWorkspace = Workspace::factory()->create();
    $workspace->users()->attach($user, [
        'role' => WorkspaceRole::Owner->value,
    ]);
    Project::factory()->for($otherWorkspace)->create(['key' => 'API']);

    actingAs($user)
        ->postJson(route('workspaces.projects.store', $workspace), [
            'name' => 'API Project',
            'key' => 'api',
        ])
        ->assertCreated()
        ->assertJsonPath('data.key', 'API');

    assertDatabaseHas('projects', [
        'workspace_id' => $workspace->id,
        'key' => 'API',
    ]);
});

test('an owner or admin can update a project and normalize its key', function (WorkspaceRole $role) {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user, ['role' => $role->value]);
    $project = Project::factory()->for($workspace)->create([
        'name' => 'Original Project',
        'key' => 'OLD',
    ]);

    actingAs($user)
        ->patchJson(route('projects.update', $project), [
            'name' => 'Updated Project',
            'key' => 'new',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Project')
        ->assertJsonPath('data.key', 'NEW');

    assertDatabaseHas('projects', [
        'id' => $project->id,
        'workspace_id' => $workspace->id,
        'name' => 'Updated Project',
        'key' => 'NEW',
    ]);
})->with([
    'owner' => WorkspaceRole::Owner,
    'admin' => WorkspaceRole::Admin,
]);

test('a member cannot update a project', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user, [
        'role' => WorkspaceRole::Member->value,
    ]);
    $project = Project::factory()->for($workspace)->create([
        'name' => 'Original Project',
    ]);
    $project->users()->attach($user, [
        'role' => ProjectRole::Member->value,
    ]);

    actingAs($user)
        ->patchJson(route('projects.update', $project), [
            'name' => 'Forbidden Update',
        ])
        ->assertForbidden();

    expect($project->refresh()->name)->toBe('Original Project');
});

test('a project lead can update their project', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user, [
        'role' => WorkspaceRole::Member->value,
    ]);
    $project = Project::factory()->for($workspace)->create([
        'name' => 'Original Project',
    ]);
    $project->users()->attach($user, [
        'role' => ProjectRole::Lead->value,
    ]);

    actingAs($user)
        ->patchJson(route('projects.update', $project), [
            'name' => 'Lead Updated Project',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Lead Updated Project');

    expect($project->refresh()->name)->toBe('Lead Updated Project');
});

test('a project cannot be moved to another workspace through update', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $otherWorkspace = Workspace::factory()->create();
    $workspace->users()->attach($user, [
        'role' => WorkspaceRole::Owner->value,
    ]);
    $project = Project::factory()->for($workspace)->create();

    actingAs($user)
        ->patchJson(route('projects.update', $project), [
            'workspace_id' => $otherWorkspace->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('workspace_id');

    expect($project->refresh()->workspace_id)->toBe($workspace->id);
});

test('an updated project key must remain unique within its workspace', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user, [
        'role' => WorkspaceRole::Owner->value,
    ]);
    Project::factory()->for($workspace)->create(['key' => 'TAKEN']);
    $project = Project::factory()->for($workspace)->create(['key' => 'FREE']);

    actingAs($user)
        ->patchJson(route('projects.update', $project), [
            'key' => 'taken',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('key');

    expect($project->refresh()->key)->toBe('FREE');
});
