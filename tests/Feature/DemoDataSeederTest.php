<?php

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\UserSeeder;

test('the default seeder makes a project board visible to the demo user', function () {
    $this->seed();
    $this->seed();

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();
    $workspace = Workspace::query()->where('slug', 'demo-issue-flow')->firstOrFail();
    $project = Project::query()->where('workspace_id', $workspace->id)->where('key', 'DEMO')->firstOrFail();

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('workspaces', 1);
    $this->assertDatabaseCount('projects', 1);
    $this->assertDatabaseCount('issues', 5);
    $this->assertDatabaseCount('labels', 3);
    $this->assertDatabaseCount('issue_label', 5);

    $this->actingAs($user)
        ->getJson(route('workspaces.index'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $workspace->id);

    $this->getJson(route('workspaces.projects.index', $workspace))
        ->assertOk()
        ->assertJsonPath('data.0.id', $project->id)
        ->assertJsonPath('data.0.can_create_issue', true);

    $this->getJson(route('projects.issues.index', $project))
        ->assertOk()
        ->assertJsonPath('meta.total', 5)
        ->assertJsonFragment(['status' => 'todo'])
        ->assertJsonFragment(['status' => 'in_progress'])
        ->assertJsonFragment(['status' => 'done']);

    $this->postJson(route('projects.issues.store', $project), [
        'title' => 'Try creating an issue',
    ])->assertCreated()->assertJsonPath('data.number', 6);
});

test('seeding demo data does not attach the demo user to another workspace', function () {
    $otherWorkspace = Workspace::factory()->create();

    $this->seed();

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    expect($otherWorkspace->hasMember($user))->toBeFalse();
    $this->assertDatabaseCount('workspaces', 2);
});

test('seeding stops when the demo workspace slug is owned by another user', function () {
    $otherUser = User::factory()->create();
    $otherWorkspace = Workspace::factory()->create(['slug' => 'demo-issue-flow']);
    $otherWorkspace->users()->attach($otherUser, ['role' => 'owner']);
    $this->seed(UserSeeder::class);

    expect(fn () => $this->seed())
        ->toThrow(RuntimeException::class, 'The demo workspace slug belongs to another owner.');

    $this->assertDatabaseCount('projects', 0);
    $this->assertDatabaseCount('issues', 0);
});
