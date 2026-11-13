<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica que o middleware CSRF bloqueia requisições POST sem token (419)
 * e permite as mesmas requisições quando o token é enviado corretamente.
 */
class CsrfProtectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Reativa a verificação de CSRF que o Laravel desativa no ambiente de testes.
     * Laravel 12 usa ValidateCsrfToken; uma subclasse com runningUnitTests()=false
     * força a validação do token mesmo em testes.
     */
    private function enforceCsrf(): void
    {
        app()->bind(
            ValidateCsrfToken::class,
            fn (Application $app): ValidateCsrfToken => new class($app, $app['encrypter']) extends ValidateCsrfToken {
                protected function runningUnitTests(): bool
                {
                    return false;
                }
            }
        );
    }

    public function test_post_without_csrf_returns_419(): void
    {
        $this->enforceCsrf();

        $user = User::factory()->create();

        // Requisição POST sem o campo _token deve ser rejeitada com 419.
        $this->actingAs($user)
            ->post(route('wallets.store'), [
                'name' => 'Carteira Sem CSRF',
                'type' => 'checking',
                'ownership' => 'personal',
            ])
            ->assertStatus(419);
    }

    public function test_post_with_valid_csrf_token_passes(): void
    {
        $this->enforceCsrf();

        $user = User::factory()->create();

        // Com token CSRF válido na sessão, a requisição deve passar.
        $this->actingAs($user)
            ->withSession(['_token' => 'token-valido'])
            ->post(route('wallets.store'), [
                '_token' => 'token-valido',
                'name' => 'Carteira Com CSRF',
                'type' => 'checking',
                'ownership' => 'personal',
                'balance' => 0,
            ])
            ->assertRedirect();
    }
}
