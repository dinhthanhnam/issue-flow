<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('a workspace member can list workspace members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($owner, [
        'role' => WorkspaceRole::Owner->value,
    ]);
    $workspace->users()->attach($member, [
        'role' => WorkspaceRole::Member->value,
    ]);

    $response = actingAs($owner)
        ->getJson(route('workspaces.members.index', $workspace));

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'id' => $owner->id,
            'role' => WorkspaceRole::Owner->value,
        ])
        ->assertJsonFragment([
            'id' => $member->id,
            'role' => WorkspaceRole::Member->value,
        ]);
});

test('an owner can add an existing user to a workspace', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($owner, [
        'role' => WorkspaceRole::Owner->value,
    ]);

    $response = actingAs($owner)
        ->postJson(route('workspaces.members.store', $workspace), [
            'user_id' => $member->id,
            'role' => WorkspaceRole::Member->value,
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.id', $member->id)
        ->assertJsonPath('data.role', WorkspaceRole::Member->value);

    assertDatabaseHas('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
        'role' => WorkspaceRole::Member->value,
    ]);
});

test('an owner can update a workspace member role', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($owner, [
        'role' => WorkspaceRole::Owner->value,
    ]);
    $workspace->users()->attach($member, [
        'role' => WorkspaceRole::Member->value,
    ]);

    $response = actingAs($owner)
        ->patchJson(route('workspaces.members.update', [$workspace, $member]), [
            'role' => WorkspaceRole::Admin->value,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $member->id)
        ->assertJsonPath('data.role', WorkspaceRole::Admin->value);

    assertDatabaseHas('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
        'role' => WorkspaceRole::Admin->value,
    ]);
});

test('an owner can remove a workspace member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($owner, [
        'role' => WorkspaceRole::Owner->value,
    ]);
    $workspace->users()->attach($member, [
        'role' => WorkspaceRole::Member->value,
    ]);

    actingAs($owner)
        ->deleteJson(route('workspaces.members.destroy', [$workspace, $member]))
        ->assertNoContent();

    assertDatabaseMissing('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
    ]);
});
