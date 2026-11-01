<?php

declare(strict_types=1);

use App\Models\User;

/*
|--------------------------------------------------------------------------
| Account suspension enforcement outside the admin panel
|--------------------------------------------------------------------------
*/

it('rejects a suspended user with the right password and shows the suspension notice', function (): void {
    $user = User::factory()->suspended()->create();

    $this->from('/login')
        ->post('/login', ['username' => $user->username, 'email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['login' => User::SUSPENDED_MESSAGE]);

    $this->assertGuest('web');
});

it('shows the generic error to a suspended user with a wrong password (no account enumeration)', function (): void {
    $user = User::factory()->suspended()->create();

    $this->from('/login')
        ->post('/login', ['username' => $user->username, 'email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors(['login' => trans('auth.failed')]);

    $this->assertGuest('web');
});

it('logs out an active session of a user suspended mid-session', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();

    $user->forceFill(['suspended_at' => now()])->save();

    $this->get('/dashboard')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['login' => User::SUSPENDED_MESSAGE]);

    $this->assertGuest('web');
});

it('returns 403 forbidden with the notice for a suspended user\'s existing API token', function (): void {
    $user = proUser();
    $token = apiToken($user);

    $user->forceFill(['suspended_at' => now()])->save();

    $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertStatus(403)
        ->assertJsonPath('code', 'forbidden')
        ->assertJsonPath('message', User::SUSPENDED_MESSAGE);
});

it('lets an unsuspended user sign in again', function (): void {
    $user = User::factory()->suspended()->create();

    $user->forceFill(['suspended_at' => null])->save();

    $this->post('/login', ['username' => $user->username, 'email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user, 'web');
});
