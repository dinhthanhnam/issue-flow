<?php

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Issue;
use App\Models\Label;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

test('issues can be filtered by status', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->users()->attach($user, [
        'role' => WorkspaceRole::Member->value,
    ]);

    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
    ]);
    $project->users()->attach($user, ['role' => ProjectRole::Viewer->value]);

    Issue::factory()->create([
        'project_id' => $project->id,
        'reporter_id' => $user->id,
        'status' => IssueStatus::Todo,
    ]);

    Issue::factory()->create([
        'project_id' => $project->id,
        'reporter_id' => $user->id,
        'status' => IssueStatus::Done,
    ]);

    $this
        ->actingAs($user)
        ->getJson(route('projects.issues.index', [
            'project' => $project,
            'status' => 'todo',
        ]))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'todo');
});

test('issues can be filtered by priority assignee and reporter', function () {
    $viewer = User::factory()->create();
    $reporter = User::factory()->create();
    $assignee = User::factory()->create();
    $otherUser = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach([$viewer->id, $reporter->id, $assignee->id, $otherUser->id], [
        'role' => WorkspaceRole::Member->value,
    ]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($viewer, ['role' => ProjectRole::Viewer->value]);
    $matchingIssue = Issue::factory()->for($project)->create([
        'reporter_id' => $reporter->id,
        'assignee_id' => $assignee->id,
        'priority' => IssuePriority::High,
    ]);
    Issue::factory()->for($project)->create([
        'reporter_id' => $otherUser->id,
        'assignee_id' => $assignee->id,
        'priority' => IssuePriority::High,
    ]);
    Issue::factory()->for($project)->create([
        'reporter_id' => $reporter->id,
        'assignee_id' => $otherUser->id,
        'priority' => IssuePriority::High,
    ]);
    Issue::factory()->for($project)->create([
        'reporter_id' => $reporter->id,
        'assignee_id' => $assignee->id,
        'priority' => IssuePriority::Low,
    ]);

    $this->actingAs($viewer)
        ->getJson(route('projects.issues.index', [
            'project' => $project,
            'priority' => IssuePriority::High->value,
            'assignee_id' => $assignee->id,
            'reporter_id' => $reporter->id,
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingIssue->id);
});

test('issues can be filtered by labels from the project workspace', function () {
    $viewer = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($viewer, ['role' => WorkspaceRole::Member->value]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($viewer, ['role' => ProjectRole::Viewer->value]);
    $bug = Label::factory()->for($workspace)->create(['name' => 'Bug']);
    $feature = Label::factory()->for($workspace)->create(['name' => 'Feature']);
    $matchingIssue = Issue::factory()->for($project)->create();
    $otherIssue = Issue::factory()->for($project)->create();
    $matchingIssue->labels()->attach($bug);
    $otherIssue->labels()->attach($feature);

    $this->actingAs($viewer)
        ->getJson(route('projects.issues.index', [
            'project' => $project,
            'label_ids' => [$bug->id],
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingIssue->id);
});

test('issue text search matches titles and descriptions', function (array $attributes) {
    $viewer = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($viewer, ['role' => WorkspaceRole::Member->value]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($viewer, ['role' => ProjectRole::Viewer->value]);
    $matchingIssue = Issue::factory()->for($project)->create($attributes);
    Issue::factory()->for($project)->create([
        'title' => 'Unrelated issue',
        'description' => 'Nothing relevant here',
    ]);

    $this->actingAs($viewer)
        ->getJson(route('projects.issues.index', [
            'project' => $project,
            'search' => 'authentication',
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingIssue->id);
})->with([
    'title' => [['title' => 'Implement authentication']],
    'description' => [[
        'title' => 'Implement security',
        'description' => 'Authentication flow',
    ]],
]);

test('issues support stable sorting and bounded pagination', function () {
    $viewer = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($viewer, ['role' => WorkspaceRole::Member->value]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($viewer, ['role' => ProjectRole::Viewer->value]);
    Issue::factory()->for($project)->create(['number' => 3]);
    Issue::factory()->for($project)->create(['number' => 1]);
    Issue::factory()->for($project)->create(['number' => 2]);

    $this->actingAs($viewer)
        ->getJson(route('projects.issues.index', [
            'project' => $project,
            'sort_by' => 'number',
            'sort_direction' => 'asc',
            'per_page' => 2,
        ]))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.number', 1)
        ->assertJsonPath('data.1.number', 2)
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 3);
});

test('invalid issue filters are rejected', function () {
    $viewer = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $otherWorkspace = Workspace::factory()->create();
    $workspace->users()->attach($viewer, ['role' => WorkspaceRole::Member->value]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($viewer, ['role' => ProjectRole::Viewer->value]);
    $otherLabel = Label::factory()->for($otherWorkspace)->create();

    $this->actingAs($viewer)
        ->getJson(route('projects.issues.index', [
            'project' => $project,
            'priority' => 'critical',
            'label_ids' => [$otherLabel->id],
            'sort_by' => 'drop table issues',
            'per_page' => 101,
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'priority',
            'label_ids.0',
            'sort_by',
            'per_page',
        ]);
});
