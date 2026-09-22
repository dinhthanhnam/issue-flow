<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Issue;
use App\Models\Label;
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

test('archived workspaces remain readable', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['archived_at' => now()]);
    $workspace->users()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $project = Project::factory()->for($workspace)->create();
    $issue = Issue::factory()->for($project)->create(['reporter_id' => $owner->id]);

    $this->actingAs($owner)
        ->getJson(route('workspaces.show', $workspace))
        ->assertOk();

    $this->actingAs($owner)
        ->getJson(route('projects.show', $project))
        ->assertOk();

    $this->actingAs($owner)
        ->getJson(route('projects.issues.index', $project))
        ->assertOk()
        ->assertJsonPath('data.0.id', $issue->id);
});

test('archived workspaces reject mutations through every nested resource', function () {
    $owner = User::factory()->create();
    $target = User::factory()->create();
    $newMember = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'name' => 'Archived Workspace',
        'archived_at' => now(),
    ]);
    $workspace->users()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $workspace->users()->attach($target, ['role' => WorkspaceRole::Member->value]);
    $workspace->users()->attach($newMember, ['role' => WorkspaceRole::Member->value]);
    $project = Project::factory()->for($workspace)->create(['name' => 'Original Project']);
    $project->users()->attach($owner, ['role' => ProjectRole::Lead->value]);
    $project->users()->attach($target, ['role' => ProjectRole::Member->value]);
    $issue = Issue::factory()->for($project)->create([
        'reporter_id' => $owner->id,
        'title' => 'Original Issue',
    ]);
    $label = Label::factory()->for($workspace)->create(['name' => 'Original Label']);
    $issue->labels()->attach($label);

    $this->actingAs($owner)
        ->patchJson(route('workspaces.update', $workspace), ['name' => 'Changed Workspace'])
        ->assertForbidden();

    $this->actingAs($owner)
        ->postJson(route('workspaces.archive', $workspace))
        ->assertForbidden();

    $this->actingAs($owner)
        ->postJson(route('workspaces.members.store', $workspace), [
            'user_id' => User::factory()->create()->id,
            'role' => WorkspaceRole::Member->value,
        ])
        ->assertForbidden();

    $this->actingAs($owner)
        ->patchJson(route('workspaces.members.update', [$workspace, $target]), [
            'role' => WorkspaceRole::Admin->value,
        ])
        ->assertForbidden();

    $this->actingAs($owner)
        ->deleteJson(route('workspaces.members.destroy', [$workspace, $target]))
        ->assertForbidden();

    $this->actingAs($owner)
        ->postJson(route('workspaces.projects.store', $workspace), [
            'name' => 'Blocked Project',
            'key' => 'BLOCK',
        ])
        ->assertForbidden();

    $this->actingAs($owner)
        ->postJson(route('workspaces.labels.store', $workspace), [
            'name' => 'Blocked Label',
            'color' => '#123456',
        ])
        ->assertForbidden();

    $this->actingAs($owner)
        ->patchJson(route('projects.update', $project), ['name' => 'Changed Project'])
        ->assertForbidden();

    $this->actingAs($owner)
        ->postJson(route('projects.members.store', $project), [
            'user_id' => $newMember->id,
            'role' => ProjectRole::Member->value,
        ])
        ->assertForbidden();

    $this->actingAs($owner)
        ->patchJson(route('projects.members.update', [$project, $target]), [
            'role' => ProjectRole::Viewer->value,
        ])
        ->assertForbidden();

    $this->actingAs($owner)
        ->deleteJson(route('projects.members.destroy', [$project, $target]))
        ->assertForbidden();

    $this->actingAs($owner)
        ->patchJson(route('issues.update', $issue), ['title' => 'Changed Issue'])
        ->assertForbidden();

    $this->actingAs($owner)
        ->postJson(route('issues.comments.store', $issue), ['body' => 'Blocked comment'])
        ->assertForbidden();

    $this->actingAs($owner)
        ->postJson(route('issues.labels.store', [$issue, $label]))
        ->assertForbidden();

    $this->actingAs($owner)
        ->deleteJson(route('issues.labels.destroy', [$issue, $label]))
        ->assertForbidden();

    $this->actingAs($owner)
        ->patchJson(route('labels.update', $label), ['name' => 'Changed Label'])
        ->assertForbidden();

    $this->actingAs($owner)
        ->deleteJson(route('labels.destroy', $label))
        ->assertForbidden();

    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'name' => 'Archived Workspace',
    ]);
    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Original Project',
    ]);
    $this->assertDatabaseHas('issues', [
        'id' => $issue->id,
        'title' => 'Original Issue',
    ]);
    $this->assertDatabaseHas('labels', [
        'id' => $label->id,
        'name' => 'Original Label',
    ]);
    $this->assertDatabaseMissing('comments', [
        'issue_id' => $issue->id,
        'body' => 'Blocked comment',
    ]);
});
