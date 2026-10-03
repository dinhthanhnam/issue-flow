<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

use function Pest\Laravel\actingAs;

test('a workspace owner can find an existing user by exact email', function () {
    $owner = User::factory()->create();
    $candidate = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($owner, ['role' => WorkspaceRole::Owner->value]);

    actingAs($owner)
        ->getJson(route('workspaces.member-candidate.show', [
            'workspace' => $workspace,
            'email' => $candidate->email,
        ]))
        ->assertOk()
        ->assertJsonPath('data.id', $candidate->id)
        ->assertJsonPath('data.name', $candidate->name)
        ->assertJsonPath('data.email', $candidate->email);
});

test('a workspace member cannot look up member candidates', function () {
    $member = User::factory()->create();
    $candidate = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($member, ['role' => WorkspaceRole::Member->value]);

    actingAs($member)
        ->getJson(route('workspaces.member-candidate.show', [
            'workspace' => $workspace,
            'email' => $candidate->email,
        ]))
        ->assertForbidden();
});

test('an existing workspace member is not returned as a candidate', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $workspace->users()->attach($member, ['role' => WorkspaceRole::Member->value]);

    actingAs($owner)
        ->getJson(route('workspaces.member-candidate.show', [
            'workspace' => $workspace,
            'email' => $member->email,
        ]))
        ->assertNotFound();
});

test('a project lead can find a workspace member not yet in the project', function () {
    $lead = User::factory()->create();
    $candidate = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $project = Project::factory()->for($workspace)->create();
    $workspace->users()->attach([$lead->id, $candidate->id], [
        'role' => WorkspaceRole::Member->value,
    ]);
    $project->users()->attach($lead, ['role' => ProjectRole::Lead->value]);

    actingAs($lead)
        ->getJson(route('projects.member-candidate.show', [
            'project' => $project,
            'email' => $candidate->email,
        ]))
        ->assertOk()
        ->assertJsonPath('data.id', $candidate->id)
        ->assertJsonPath('data.email', $candidate->email);
});

test('a user outside the workspace is not returned as a project candidate', function () {
    $lead = User::factory()->create();
    $outsider = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $project = Project::factory()->for($workspace)->create();
    $workspace->users()->attach($lead, ['role' => WorkspaceRole::Member->value]);
    $project->users()->attach($lead, ['role' => ProjectRole::Lead->value]);

    actingAs($lead)
        ->getJson(route('projects.member-candidate.show', [
            'project' => $project,
            'email' => $outsider->email,
        ]))
        ->assertNotFound();
});

test('member candidate lookup requires a valid email', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($owner, ['role' => WorkspaceRole::Owner->value]);

    actingAs($owner)
        ->getJson(route('workspaces.member-candidate.show', [
            'workspace' => $workspace,
            'email' => 'not-an-email',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});
