<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRUD básico de carteiras: criação, limite do plano gratuito, edição,
 * exclusão e validação do pagamento de fatura.
 */
class WalletCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function checkingPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Conta Corrente',
            'type' => 'checking',
            'ownership' => 'personal',
            'balance' => 1000,
            'color' => '#3B82F6',
        ], $overrides);
    }

    public function test_authenticated_user_can_create_wallet(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('wallets.store'), $this->checkingPayload());

        $response->assertRedirect(route('wallets.index'));
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->user->id,
            'name' => 'Conta Corrente',
            'type' => 'checking',
        ]);
    }

    public function test_free_plan_cannot_create_second_wallet(): void
    {
        // Plano gratuito: 1 carteira apenas.
        Wallet::factory()->for($this->user)->create(['type' => 'checking']);

        $response = $this->actingAs($this->user)
            ->post(route('wallets.store'), $this->checkingPayload(['name' => 'Segunda Carteira']));

        $response->assertRedirect(route('wallets.index'));
        $response->assertSessionHas('error');

        $this->assertSame(1, $this->user->wallets()->count());
    }

    public function test_user_can_update_own_wallet(): void
    {
        $wallet = Wallet::factory()->for($this->user)->create(['type' => 'checking', 'name' => 'Nubank']);

        $response = $this->actingAs($this->user)
            ->put(route('wallets.update', $wallet), ['name' => 'Nubank Atualizado']);

        $response->assertRedirect(route('wallets.index'));
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'name' => 'Nubank Atualizado',
        ]);
    }

    public function test_user_can_delete_own_wallet(): void
    {
        $wallet = Wallet::factory()->for($this->user)->create(['type' => 'checking']);

        $response = $this->actingAs($this->user)
            ->delete(route('wallets.destroy', $wallet));

        $response->assertRedirect(route('wallets.index'));
        $this->assertSoftDeleted('wallets', ['id' => $wallet->id]);
    }

    public function test_pay_invoice_requires_valid_ref(): void
    {
        $card = Wallet::factory()->for($this->user)->create([
            'type' => 'credit',
            'balance' => -200,
            'credit_limit' => 2000,
            'closing_day' => 5,
            'due_day' => 15,
        ]);
        $checking = Wallet::factory()->for($this->user)->create(['type' => 'checking', 'balance' => 1000]);

        $response = $this->actingAs($this->user)
            ->post(route('wallets.invoice.pay', $card), [
                'source_wallet_id' => $checking->id,
                'amount' => '200.00',
                'date' => now()->toDateString(),
                'ref' => 'invalid', // deve falhar na regex /^\d{4}-\d{2}$/
            ]);

        $response->assertSessionHasErrors('ref');
    }

    public function test_pay_invoice_source_cannot_be_same_as_card(): void
    {
        $card = Wallet::factory()->for($this->user)->create([
            'type' => 'credit',
            'balance' => -200,
            'credit_limit' => 2000,
            'closing_day' => 5,
            'due_day' => 15,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('wallets.invoice.pay', $card), [
                'source_wallet_id' => $card->id, // mesmo cartão — deve falhar
                'amount' => '200.00',
                'date' => now()->toDateString(),
                'ref' => now()->format('Y-m'),
            ]);

        $response->assertSessionHasErrors('source_wallet_id');
    }
}
