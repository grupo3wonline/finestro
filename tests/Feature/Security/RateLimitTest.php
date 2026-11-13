<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica que o limitador de taxa (rate limiting) bloqueia excesso de
 * tentativas de login com o mesmo usuário/IP.
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_too_many_login_attempts_returns_429(): void
    {
        $this->freezeTime();

        $user = User::factory()->create();
        $credentials = [
            'username' => $user->username,
            'email' => $user->email,
            'password' => 'senha-errada',
        ];

        // A LoginRequest permite 5 tentativas; a 6.ª retorna erro de throttle.
        foreach (range(1, 5) as $attempt) {
            $this->from('/login')
                ->post('/login', $credentials)
                ->assertSessionHasErrors('login');
        }

        // A 6.ª tentativa deve acionar o rate limiter.
        $response = $this->from('/login')->post('/login', $credentials);

        $response->assertSessionHasErrors('login');

        // A mensagem de erro da 6.ª tentativa deve conter a mensagem de throttle.
        $errors = session('errors');
        $this->assertNotNull($errors);
        $loginError = $errors->first('login');
        $this->assertStringContainsString(
            trans('auth.throttle', ['seconds' => 60, 'minutes' => 1]),
            $loginError
        );
    }
}
