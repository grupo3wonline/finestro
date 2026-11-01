<?php

namespace Tests\Unit;

use App\Models\AiCredential;
use App\Models\Budget;
use App\Models\PaymentHistory;
use App\Models\TransactionHistory;
use App\Models\WalletMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Garante que todas as factories recém-criadas produzem registros válidos
 * contra o schema real (cobre models que antes não tinham factory).
 */
class FactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_factory_persists(): void
    {
        $budget = Budget::factory()->create();

        $this->assertDatabaseHas('budgets', ['id' => $budget->id]);
        $this->assertNull($budget->category_id, 'Orçamento padrão é geral (sem categoria).');
        $this->assertGreaterThan(0, (float) $budget->amount);
    }

    public function test_ai_credential_factory_persists_and_encrypts(): void
    {
        $credential = AiCredential::factory()->create();

        $this->assertDatabaseHas('ai_credentials', ['id' => $credential->id]);
        $this->assertTrue($credential->hasConfiguredProvider());

        // A chave é gravada cifrada: o valor cru na coluna difere do valor decifrado.
        $raw = \DB::table('ai_credentials')->where('id', $credential->id)->value('gemini_api_key');
        $this->assertNotSame($credential->gemini_api_key, $raw);
    }

    public function test_ai_credential_provider_states(): void
    {
        $this->assertSame('groq', AiCredential::factory()->groq()->create()->preferred_provider);
        $this->assertSame('openrouter', AiCredential::factory()->openRouter()->create()->preferred_provider);
    }

    public function test_payment_history_factory_states(): void
    {
        $this->assertSame('paid', PaymentHistory::factory()->create()->status);
        $this->assertSame('failed', PaymentHistory::factory()->failed()->create()->status);
        $this->assertNull(PaymentHistory::factory()->failed()->create()->paid_at);
    }

    public function test_wallet_member_factory_states(): void
    {
        $member = WalletMember::factory()->role('admin')->create();
        $this->assertSame('admin', $member->role);
        $this->assertTrue($member->canManage());

        $this->assertNull(WalletMember::factory()->pending()->create()->accepted_at);
    }

    public function test_transaction_history_factory_persists(): void
    {
        $history = TransactionHistory::factory()->action(TransactionHistory::ACTION_CREATED)->create();

        $this->assertDatabaseHas('transaction_histories', ['id' => $history->id]);
        $this->assertSame('Criada', $history->action_label);
    }
}
