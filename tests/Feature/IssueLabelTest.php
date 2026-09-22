<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Issue;
use App\Models\Label;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('a project contributor can attach a workspace label to an issue', function (ProjectRole $role) {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user, ['role' => WorkspaceRole::Member->value]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($user, ['role' => $role->value]);
    $issue = Issue::factory()->for($project)->create(['reporter_id' => $user->id]);
    $label = Label::factory()->for($workspace)->create();

    actingAs($user)
        ->postJson(route('issues.labels.store', [$issue, $label]))
        ->assertNoContent();

    assertDatabaseHas('issue_label', [
        'issue_id' => $issue->id,
        'label_id' => $label->id,
    ]);
})->with([
    'project lead' => ProjectRole::Lead,
    'project member' => ProjectRole::Member,
]);

test('attaching the same label twice remains idempotent', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user, ['role' => WorkspaceRole::Member->value]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($user, ['role' => ProjectRole::Member->value]);
    $issue = Issue::factory()->for($project)->create(['reporter_id' => $user->id]);
    $label = Label::factory()->for($workspace)->create();
    $issue->labels()->attach($label);

    actingAs($user)
        ->postJson(route('issues.labels.store', [$issue, $label]))
        ->assertNoContent();

    expect($issue->labels()->whereKey($label->id)->count())->toBe(1);
});

test('a project viewer cannot attach a label', function () {
    $viewer = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($viewer, ['role' => WorkspaceRole::Member->value]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($viewer, ['role' => ProjectRole::Viewer->value]);
    $issue = Issue::factory()->for($project)->create();
    $label = Label::factory()->for($workspace)->create();

    actingAs($viewer)
        ->postJson(route('issues.labels.store', [$issue, $label]))
        ->assertForbidden();

    assertDatabaseMissing('issue_label', [
        'issue_id' => $issue->id,
        'label_id' => $label->id,
    ]);
});

test('a label from another workspace cannot be attached to an issue', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $otherWorkspace = Workspace::factory()->create();
    $workspace->users()->attach($user, ['role' => WorkspaceRole::Member->value]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($user, ['role' => ProjectRole::Member->value]);
    $issue = Issue::factory()->for($project)->create(['reporter_id' => $user->id]);
    $label = Label::factory()->for($otherWorkspace)->create();

    actingAs($user)
        ->postJson(route('issues.labels.store', [$issue, $label]))
        ->assertForbidden();

    assertDatabaseMissing('issue_label', [
        'issue_id' => $issue->id,
        'label_id' => $label->id,
    ]);
});

test('a project contributor can detach a label from an issue', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user, ['role' => WorkspaceRole::Member->value]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($user, ['role' => ProjectRole::Member->value]);
    $issue = Issue::factory()->for($project)->create(['reporter_id' => $user->id]);
    $label = Label::factory()->for($workspace)->create();
    $issue->labels()->attach($label);

    actingAs($user)
        ->deleteJson(route('issues.labels.destroy', [$issue, $label]))
        ->assertNoContent();

    assertDatabaseMissing('issue_label', [
        'issue_id' => $issue->id,
        'label_id' => $label->id,
    ]);
});

test('issue responses expose eager loaded labels', function () {
    $viewer = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($viewer, ['role' => WorkspaceRole::Member->value]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($viewer, ['role' => ProjectRole::Viewer->value]);
    $issue = Issue::factory()->for($project)->create();
    $label = Label::factory()->for($workspace)->create([
        'name' => 'Bug',
        'color' => '#DC2626',
    ]);
    $issue->labels()->attach($label);

    actingAs($viewer)
        ->getJson(route('projects.issues.index', $project))
        ->assertOk()
        ->assertJsonPath('data.0.labels.0.id', $label->id)
        ->assertJsonPath('data.0.labels.0.name', 'Bug');
});
