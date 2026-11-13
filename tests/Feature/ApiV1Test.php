<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ApiTokenAbility;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Wallet;
use Database\Factories\SubscriptionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes de integração para a API REST v1 (Sanctum):
 * autenticação, CRUD de carteiras e transações, e isolamento entre usuários.
 */
class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    /** Cria um usuário no plano Profissional (tem acesso à API). */
    private function proUser(): User
    {
        $user = User::factory()->create();
        SubscriptionFactory::new()->for($user)->create(['current_period_end' => now()->addMonth()]);

        return $user->fresh();
    }

    /** Gera um token com todos os escopos para o usuário. */
    private function apiToken(User $user, array $abilities = ['*']): string
    {
        return $user->createToken('test', $abilities)->plainTextToken;
    }

    public function test_guest_cannot_access_api(): void
    {
        $this->getJson('/api/v1/wallets')
            ->assertUnauthorized();
    }

    public function test_user_can_create_api_token(): void
    {
        $user = $this->proUser();

        $response = $this->actingAs($user)
            ->post(route('settings.tokens.store'), [
                'name' => 'Token de Teste',
                'abilities' => [ApiTokenAbility::WALLETS_READ->value],
            ]);

        $response->assertRedirect(route('settings.tokens.index'));
        $response->assertSessionHas('plain_token');
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Token de Teste',
        ]);
    }

    public function test_api_wallet_crud(): void
    {
        $user = $this->proUser();
        $token = $this->apiToken($user);

        // Listar carteiras (vazia no início)
        $this->withToken($token)
            ->getJson('/api/v1/wallets')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);

        // Criar carteira
        $createResponse = $this->withToken($token)
            ->postJson('/api/v1/wallets', [
                'name' => 'Conta Corrente API',
                'type' => 'checking',
                'ownership' => 'personal',
                'balance' => 1500,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Conta Corrente API');

        $walletId = $createResponse->json('data.id');

        // Atualizar carteira
        $this->withToken($token)
            ->putJson("/api/v1/wallets/{$walletId}", ['name' => 'Conta Corrente Atualizada'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Conta Corrente Atualizada');

        // Excluir carteira
        $this->withToken($token)
            ->deleteJson("/api/v1/wallets/{$walletId}")
            ->assertNoContent();
    }

    public function test_api_transaction_crud(): void
    {
        $user = $this->proUser();
        $token = $this->apiToken($user);
        $wallet = Wallet::factory()->for($user)->create(['type' => 'checking', 'balance' => 2000]);

        // Criar transação
        $this->withToken($token)
            ->postJson('/api/v1/transactions', [
                'wallet_id' => $wallet->id,
                'type' => 'expense',
                'amount' => 150,
                'description' => 'Almoço API',
                'date' => now()->toDateString(),
            ])
            ->assertCreated();

        // Listar transações
        $this->withToken($token)
            ->getJson('/api/v1/transactions')
            ->assertOk()
            ->assertJsonFragment(['description' => 'Almoço API']);
    }

    public function test_api_cross_user_isolation(): void
    {
        $userA = $this->proUser();
        $userB = $this->proUser();

        $walletA = Wallet::factory()->for($userA)->create(['type' => 'checking', 'balance' => 500]);
        $tokenB = $this->apiToken($userB);

        // Usuário B não pode acessar a carteira do Usuário A.
        $this->withToken($tokenB)
            ->getJson("/api/v1/wallets/{$walletA->id}")
            ->assertNotFound();
    }
}
