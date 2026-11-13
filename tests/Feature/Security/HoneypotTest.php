<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica que os formulários de registro e login rejeitam submissões em que
 * o campo honeypot ("website") foi preenchido — comportamento típico de bots.
 */
class HoneypotTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_with_honeypot_filled_is_rejected(): void
    {
        $response = $this->post('/register', [
            'name' => 'Bot Teste',
            'username' => 'botteste',
            'email' => 'bot@spam.com',
            'password' => 'senha-valida-123',
            'password_confirmation' => 'senha-valida-123',
            'terms' => '1',
            'website' => 'bot@spam.com', // campo honeypot preenchido
        ]);

        // Um bot com o campo preenchido deve receber erro de validação
        // e NÃO ser redirecionado para a tela de login (registro bem-sucedido).
        $response->assertSessionHasErrors('website');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'bot@spam.com']);
    }

    public function test_login_with_honeypot_filled_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'username' => $user->username,
            'email' => $user->email,
            'password' => 'password',
            'website' => 'bot@spam.com', // campo honeypot preenchido
        ]);

        // O login com honeypot preenchido deve falhar na validação.
        $response->assertSessionHasErrors('website');
        $this->assertGuest();
    }
}
