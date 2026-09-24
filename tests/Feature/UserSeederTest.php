<?php

use App\Models\User;

test('the default seeder creates a login account and can be rerun', function () {
    $this->seed();
    $this->seed();

    $this->assertDatabaseCount('users', 1);

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    $this->post(route('login.store'), [
        'email' => 'test@example.com',
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('the default seeder does not change an existing account', function () {
    $user = User::factory()->create([
        'name' => 'Existing User',
        'email' => 'test@example.com',
    ]);
    $originalPassword = $user->password;

    $this->seed();

    $this->assertDatabaseCount('users', 1);
    expect($user->fresh()->name)->toBe('Existing User');
    expect($user->fresh()->password)->toBe($originalPassword);
});
