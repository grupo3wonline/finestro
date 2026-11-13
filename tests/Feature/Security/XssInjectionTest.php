<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica proteção contra XSS (Cross-Site Scripting) e injeção de SQL:
 * payloads maliciosos devem ser armazenados e exibidos de forma segura.
 */
class XssInjectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->wallet = Wallet::factory()->for($this->user)->create([
            'type' => 'checking',
            'balance' => 1000,
        ]);
    }

    public function test_script_tag_in_description_is_stored_escaped(): void
    {
        $xssPayload = '<script>alert(1)</script>';

        // Cria a transação com payload XSS na descrição.
        $this->actingAs($this->user)->post(route('transactions.store'), [
            'wallet_id' => $this->wallet->id,
            'type' => 'expense',
            'amount' => '50.00',
            'description' => $xssPayload,
            'date' => now()->toDateString(),
        ])->assertRedirect();

        $transaction = Transaction::where('user_id', $this->user->id)->firstOrFail();

        // O Blade escapa a saída por padrão; a tag <script> não deve aparecer
        // como HTML literal na resposta.
        $this->actingAs($this->user)
            ->get(route('transactions.show', $transaction))
            ->assertOk()
            ->assertDontSee($xssPayload, false)        // tag literal não aparece
            ->assertSee('&lt;script&gt;', false);      // versão escapada é exibida
    }

    public function test_malformed_search_does_not_cause_500(): void
    {
        $sqlInjectionPayload = "' OR '1'='1";

        // Busca com payload de SQL injection não deve causar erro 500.
        $this->actingAs($this->user)
            ->get(route('transactions.index', ['search' => $sqlInjectionPayload, 'month' => 'all']))
            ->assertOk();
    }
}
