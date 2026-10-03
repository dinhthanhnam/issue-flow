<?php

use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

test('the home page leads guests to the login screen', function () {
    $this->followingRedirects()
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/login'));
});

test('the home page leads authenticated users to the dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->followingRedirects()
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('dashboard'));
});

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('workspaces', [])
            ->where('workspace', null)
            ->where('projects', [])
            ->where('labels', [])
            ->where('project', null)
            ->where('assignees', [])
            ->where('issues', null));
});

test('dashboard provides the selected workspace project and issues', function () {
    $this->seed();
    $user = User::query()->where('email', 'test@example.com')->firstOrFail();
    $workspace = Workspace::query()->where('slug', 'demo-issue-flow')->firstOrFail();
    $project = $workspace->projects()->where('key', 'DEMO')->firstOrFail();

    $this->actingAs($user)
        ->get(route('dashboard', [
            'workspace' => $workspace->id,
            'project' => $project->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('workspace.id', $workspace->id)
            ->where('project.id', $project->id)
            ->has('workspaces', 1)
            ->has('projects', 1)
            ->has('labels', 3)
            ->has('assignees', 1)
            ->where('assignees.0.id', $user->id)
            ->has('issues.data', 5));
});
