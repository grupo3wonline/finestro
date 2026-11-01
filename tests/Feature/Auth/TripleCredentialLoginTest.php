<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Testing\TestResponse;

/*
|--------------------------------------------------------------------------
| Login requires username + e-mail + password of the same account
|--------------------------------------------------------------------------
*/

/**
 * @param  array<string, string>  $credentials
 */
function loginAs(array $credentials): TestResponse
{
    return test()->from('/login')->post('/login', $credentials);
}

it('signs in when username, e-mail and password belong to the same user', function (): void {
    $user = User::factory()->create(['username' => 'ana.souza', 'email' => 'ana@example.com']);

    loginAs(['username' => 'ana.souza', 'email' => 'ana@example.com', 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user, 'web');
});

it('returns the same generic error for every credential mismatch', function (array $credentials): void {
    User::factory()->create(['username' => 'ana.souza', 'email' => 'ana@example.com']);
    User::factory()->create(['username' => 'bruno.lima', 'email' => 'bruno@example.com']);

    loginAs($credentials)
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['login' => trans('auth.failed')]);

    $this->assertGuest('web');
})->with([
    'wrong username' => [['username' => 'ana.errada', 'email' => 'ana@example.com', 'password' => 'password']],
    'wrong e-mail' => [['username' => 'ana.souza', 'email' => 'outra@example.com', 'password' => 'password']],
    'username of A + e-mail of B' => [['username' => 'ana.souza', 'email' => 'bruno@example.com', 'password' => 'password']],
    'wrong password' => [['username' => 'ana.souza', 'email' => 'ana@example.com', 'password' => 'senha-errada']],
    'unknown account' => [['username' => 'ninguem', 'email' => 'ninguem@example.com', 'password' => 'password']],
]);

it('requires all three fields', function (): void {
    loginAs([])->assertSessionHasErrors(['username', 'email', 'password']);

    $this->assertGuest('web');
});

it('locks the username + e-mail pair after 5 failed attempts', function (): void {
    $this->freezeTime();
    $user = User::factory()->create();
    $credentials = ['username' => $user->username, 'email' => $user->email, 'password' => 'senha-errada'];

    foreach (range(1, 5) as $attempt) {
        loginAs($credentials)->assertSessionHasErrors(['login' => trans('auth.failed')]);
    }

    // Even the right password is refused while locked.
    loginAs([...$credentials, 'password' => 'password'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['login' => trans('auth.throttle', ['seconds' => 60, 'minutes' => 1])]);

    $this->assertGuest('web');
});

it('matches the e-mail case-insensitively', function (): void {
    $user = User::factory()->create(['username' => 'carla', 'email' => 'carla@example.com']);

    loginAs(['username' => 'carla', 'email' => 'CARLA@Example.COM', 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user, 'web');
});

it('uses a username placeholder that passes the alpha_dash rule', function (): void {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('placeholder="seu_usuario"', false)
        ->assertDontSee('seu.usuario', false);

    expect(validator(['username' => 'seu_usuario'], ['username' => 'alpha_dash'])->passes())->toBeTrue();
});
