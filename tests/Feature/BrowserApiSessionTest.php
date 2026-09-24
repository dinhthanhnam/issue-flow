<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;

test('a browser session can use the protected workspace API', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->users()->attach($user, [
        'role' => WorkspaceRole::Owner->value,
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->withHeaders([
        'Origin' => config('app.url'),
        'Referer' => route('dashboard'),
    ])->getJson(route('workspaces.index'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $workspace->id);
});
