<?php

use App\Enums\WorkspaceRole;
use App\Models\Label;
use App\Models\User;
use App\Models\Workspace;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('a workspace member can list labels', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user, ['role' => WorkspaceRole::Member->value]);
    $label = Label::factory()->for($workspace)->create();

    actingAs($user)
        ->getJson(route('workspaces.labels.index', $workspace))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $label->id);
});

test('a workspace manager can create a label with a normalized color', function (WorkspaceRole $role) {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user, ['role' => $role->value]);

    actingAs($user)
        ->postJson(route('workspaces.labels.store', $workspace), [
            'name' => 'Backend',
            'color' => '#a1b2c3',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Backend')
        ->assertJsonPath('data.color', '#A1B2C3');

    assertDatabaseHas('labels', [
        'workspace_id' => $workspace->id,
        'name' => 'Backend',
        'color' => '#A1B2C3',
    ]);
})->with([
    'owner' => WorkspaceRole::Owner,
    'admin' => WorkspaceRole::Admin,
]);

test('a workspace member cannot create a label', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user, ['role' => WorkspaceRole::Member->value]);

    actingAs($user)
        ->postJson(route('workspaces.labels.store', $workspace), [
            'name' => 'Forbidden',
            'color' => '#123456',
        ])
        ->assertForbidden();

    assertDatabaseMissing('labels', [
        'workspace_id' => $workspace->id,
        'name' => 'Forbidden',
    ]);
});

test('label names must be unique within a workspace', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    Label::factory()->for($workspace)->create(['name' => 'Bug']);

    actingAs($owner)
        ->postJson(route('workspaces.labels.store', $workspace), [
            'name' => 'Bug',
            'color' => '#DC2626',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('different workspaces may use the same label name', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $otherWorkspace = Workspace::factory()->create();
    $workspace->users()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    Label::factory()->for($otherWorkspace)->create(['name' => 'Bug']);

    actingAs($owner)
        ->postJson(route('workspaces.labels.store', $workspace), [
            'name' => 'Bug',
            'color' => '#DC2626',
        ])
        ->assertCreated();

    assertDatabaseHas('labels', [
        'workspace_id' => $workspace->id,
        'name' => 'Bug',
    ]);
});

test('a workspace manager can update a label', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $label = Label::factory()->for($workspace)->create([
        'name' => 'Old',
        'color' => '#111111',
    ]);

    actingAs($owner)
        ->patchJson(route('labels.update', $label), [
            'name' => 'New',
            'color' => '#abcdef',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'New')
        ->assertJsonPath('data.color', '#ABCDEF');

    assertDatabaseHas('labels', [
        'id' => $label->id,
        'workspace_id' => $workspace->id,
        'name' => 'New',
        'color' => '#ABCDEF',
    ]);
});

test('a label cannot be moved to another workspace through update', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $otherWorkspace = Workspace::factory()->create();
    $workspace->users()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $label = Label::factory()->for($workspace)->create([
        'name' => 'Old',
        'color' => '#111111',
    ]);

    actingAs($owner)
        ->patchJson(route('labels.update', $label), [
            'name' => 'New',
            'color' => '#abcdef',
            'workspace_id' => $otherWorkspace->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('workspace_id');

    $label->refresh();

    expect($label->name)->toBe('Old')
        ->and($label->workspace_id)->toBe($workspace->id);
});

test('a workspace manager can delete a label', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $label = Label::factory()->for($workspace)->create();

    actingAs($owner)
        ->deleteJson(route('labels.destroy', $label))
        ->assertNoContent();

    assertDatabaseMissing('labels', ['id' => $label->id]);
});
