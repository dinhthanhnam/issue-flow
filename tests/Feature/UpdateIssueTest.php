<?php

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\IssueAssigned;
use Illuminate\Support\Facades\Notification;

test('a workspace member can update an issue', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->users()->attach($user, [
        'role' => 'member',
    ]);

    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
    ]);
    $project->users()->attach($user, ['role' => ProjectRole::Member->value]);

    $issue = Issue::factory()->create([
        'project_id' => $project->id,
        'reporter_id' => $user->id,
        'status' => IssueStatus::Todo,
        'priority' => IssuePriority::Medium,
    ]);

    Notification::fake();

    $this
        ->actingAs($user)
        ->patchJson(route('issues.update', $issue), [
            'status' => 'in_progress',
            'priority' => 'high',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'in_progress')
        ->assertJsonPath('data.priority', 'high');

    $this->assertDatabaseHas('issues', [
        'id' => $issue->id,
        'status' => 'in_progress',
        'priority' => 'high',
    ]);

    Notification::assertNothingSent();
});

test('assigning an issue queues a notification for the assignee', function () {
    $user = User::factory()->create();
    $assignee = User::factory()->create();

    $workspace = Workspace::factory()->create();
    $workspace->users()->attach([$user->id, $assignee->id], [
        'role' => WorkspaceRole::Member->value,
    ]);

    $project = Project::factory()
        ->for($workspace)
        ->create();
    $project->users()->attach($user, ['role' => ProjectRole::Member->value]);
    $project->users()->attach($assignee, ['role' => ProjectRole::Member->value]);

    $issue = Issue::factory()
        ->for($project)
        ->create([
            'assignee_id' => null,
        ]);

    Notification::fake();

    $this->actingAs($user)
        ->patchJson(route('issues.update', $issue), [
            'assignee_id' => $assignee->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.assignee.id', $assignee->id);

    $this->assertDatabaseHas('issues', [
        'id' => $issue->id,
        'assignee_id' => $assignee->id,
    ]);

    Notification::assertSentTo(
        $assignee,
        function (IssueAssigned $notification, array $channels) use ($issue): bool {
            return $notification->issue->is($issue)
                && $channels === ['database'];
        },
    );
});

test('a project viewer cannot update an issue', function () {
    $viewer = User::factory()->create();
    $reporter = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach([$viewer->id, $reporter->id], [
        'role' => WorkspaceRole::Member->value,
    ]);
    $project = Project::factory()->for($workspace)->create();
    $project->users()->attach($viewer, ['role' => ProjectRole::Viewer->value]);
    $issue = Issue::factory()->for($project)->create([
        'reporter_id' => $reporter->id,
        'status' => IssueStatus::Todo,
    ]);

    $this->actingAs($viewer)
        ->patchJson(route('issues.update', $issue), [
            'status' => IssueStatus::Done->value,
        ])
        ->assertForbidden();

    expect($issue->refresh()->status)->toBe(IssueStatus::Todo);
});
