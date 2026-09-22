<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

test('a user can create a workspace and becomes its owner', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->postJson(route('workspaces.store'), [
        'name' => 'Test Workspace',
        'slug' => 'test-workspace',
    ]);

    $workspace = Workspace::query()
        ->where('slug', 'test-workspace')
        ->sole();

    $response
        ->assertCreated()
        ->assertJsonPath('data.id', $workspace->id)
        ->assertJsonPath('data.name', 'Test Workspace')
        ->assertJsonPath('data.slug', 'test-workspace');

    assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'name' => 'Test Workspace',
        'slug' => 'test-workspace',
    ]);

    assertDatabaseHas('user_workspace', [
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
        'role' => WorkspaceRole::Owner->value,
    ]);
});

test('a user can view a workspace they are a member of', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user, ['role' => WorkspaceRole::Member->value]);

    $response = actingAs($user)->getJson(route('workspaces.show', $workspace));

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $workspace->id)
        ->assertJsonPath('data.name', $workspace->name)
        ->assertJsonPath('data.slug', $workspace->slug);
});

test('an unauthenticated user cannot list workspaces', function () {
    $this->getJson(route('workspaces.index'))
        ->assertUnauthorized();
});

test('a user can only list workspaces they belong to with their role', function () {
    $user = User::factory()->create();
    $ownedWorkspace = Workspace::factory()->create();
    $memberWorkspace = Workspace::factory()->create();
    $outsideWorkspace = Workspace::factory()->create();

    $ownedWorkspace->users()->attach($user, [
        'role' => WorkspaceRole::Owner->value,
    ]);
    $memberWorkspace->users()->attach($user, [
        'role' => WorkspaceRole::Member->value,
    ]);

    $response = actingAs($user)->getJson(route('workspaces.index'));

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'id' => $ownedWorkspace->id,
            'role' => WorkspaceRole::Owner->value,
        ])
        ->assertJsonFragment([
            'id' => $memberWorkspace->id,
            'role' => WorkspaceRole::Member->value,
        ])
        ->assertJsonMissing([
            'id' => $outsideWorkspace->id,
        ]);
});

test('a user cannot view a workspace they do not belong to', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();

    actingAs($user)
        ->getJson(route('workspaces.show', $workspace))
        ->assertForbidden();
});

test('a member cannot update a workspace', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'name' => 'Original Workspace',
    ]);
    $workspace->users()->attach($user, [
        'role' => WorkspaceRole::Member->value,
    ]);

    actingAs($user)
        ->patchJson(route('workspaces.update', $workspace), [
            'name' => 'Updated Workspace',
        ])
        ->assertForbidden();

    assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'name' => 'Original Workspace',
    ]);
});

test('an owner can update a workspace without changing its archive state', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'name' => 'Original Workspace',
        'archived_at' => null,
    ]);
    $workspace->users()->attach($user, [
        'role' => WorkspaceRole::Owner->value,
    ]);

    $response = actingAs($user)
        ->patchJson(route('workspaces.update', $workspace), [
            'name' => 'Updated Workspace',
            'archived_at' => now()->toISOString(),
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Workspace')
        ->assertJsonPath('data.archived_at', null);

    assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'name' => 'Updated Workspace',
        'archived_at' => null,
    ]);
});

test('an owner can archive a workspace', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'archived_at' => null,
    ]);
    $workspace->users()->attach($user, [
        'role' => WorkspaceRole::Owner->value,
    ]);

    $response = actingAs($user)
        ->postJson(route('workspaces.archive', $workspace));

    $response->assertOk();

    expect($response->json('data.archived_at'))->not->toBeNull()
        ->and($workspace->refresh()->archived_at)->not->toBeNull();
});

test('a member cannot archive a workspace', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'archived_at' => null,
    ]);
    $workspace->users()->attach($user, [
        'role' => WorkspaceRole::Member->value,
    ]);

    actingAs($user)
        ->postJson(route('workspaces.archive', $workspace))
        ->assertForbidden();

    expect($workspace->refresh()->archived_at)->toBeNull();
});

test('an owner can restore an archived workspace', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'archived_at' => now(),
    ]);
    $workspace->users()->attach($user, [
        'role' => WorkspaceRole::Owner->value,
    ]);

    $response = actingAs($user)
        ->postJson(route('workspaces.restore', $workspace));

    $response
        ->assertOk()
        ->assertJsonPath('data.archived_at', null);

    expect($workspace->refresh()->archived_at)->toBeNull();
});
