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

test('a duplicate workspace membership is rejected', function () {
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
        ->postJson(route('workspaces.members.store', $workspace), [
            'user_id' => $member->id,
            'role' => WorkspaceRole::Member->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user_id');

    expect($workspace->users()->whereKey($member->id)->count())->toBe(1);
});

test('a member cannot manage workspace members', function () {
    $member = User::factory()->create();
    $newMember = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($member, [
        'role' => WorkspaceRole::Member->value,
    ]);

    actingAs($member)
        ->postJson(route('workspaces.members.store', $workspace), [
            'user_id' => $newMember->id,
            'role' => WorkspaceRole::Member->value,
        ])
        ->assertForbidden();

    assertDatabaseMissing('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $newMember->id,
    ]);
});

test('an admin cannot add another admin', function () {
    $admin = User::factory()->create();
    $newMember = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($admin, [
        'role' => WorkspaceRole::Admin->value,
    ]);

    actingAs($admin)
        ->postJson(route('workspaces.members.store', $workspace), [
            'user_id' => $newMember->id,
            'role' => WorkspaceRole::Admin->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('role');

    assertDatabaseMissing('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $newMember->id,
    ]);
});

test('an admin cannot change another admin role', function () {
    $admin = User::factory()->create();
    $targetAdmin = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($admin, [
        'role' => WorkspaceRole::Admin->value,
    ]);
    $workspace->users()->attach($targetAdmin, [
        'role' => WorkspaceRole::Admin->value,
    ]);

    actingAs($admin)
        ->patchJson(route('workspaces.members.update', [$workspace, $targetAdmin]), [
            'role' => WorkspaceRole::Member->value,
        ])
        ->assertForbidden();

    assertDatabaseHas('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $targetAdmin->id,
        'role' => WorkspaceRole::Admin->value,
    ]);
});

test('an admin cannot remove another admin', function () {
    $admin = User::factory()->create();
    $targetAdmin = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($admin, [
        'role' => WorkspaceRole::Admin->value,
    ]);
    $workspace->users()->attach($targetAdmin, [
        'role' => WorkspaceRole::Admin->value,
    ]);

    actingAs($admin)
        ->deleteJson(route('workspaces.members.destroy', [$workspace, $targetAdmin]))
        ->assertForbidden();

    assertDatabaseHas('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $targetAdmin->id,
        'role' => WorkspaceRole::Admin->value,
    ]);
});

test('an owner cannot be changed through the generic member endpoint', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($owner, [
        'role' => WorkspaceRole::Owner->value,
    ]);

    actingAs($owner)
        ->patchJson(route('workspaces.members.update', [$workspace, $owner]), [
            'role' => WorkspaceRole::Member->value,
        ])
        ->assertUnprocessable();

    assertDatabaseHas('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => WorkspaceRole::Owner->value,
    ]);
});

test('an owner cannot be removed through the generic member endpoint', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($owner, [
        'role' => WorkspaceRole::Owner->value,
    ]);

    actingAs($owner)
        ->deleteJson(route('workspaces.members.destroy', [$workspace, $owner]))
        ->assertUnprocessable();

    assertDatabaseHas('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => WorkspaceRole::Owner->value,
    ]);
});
