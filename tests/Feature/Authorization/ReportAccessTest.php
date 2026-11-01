<?php

namespace Tests\Feature\Authorization;

use App\Mail\FinancialReportMail;
use App\Models\AiCredential;
use App\Models\PaymentHistory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Database\Factories\SubscriptionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Reports, exports, subscription history and AI credentials only ever expose
 * the authenticated user's own data, even when a foreign wallet id is passed.
 */
class ReportAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $intruder;

    private Wallet $ownerWallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->professional();
        $this->intruder = $this->professional();

        $this->ownerWallet = Wallet::factory()->for($this->owner)->create(['type' => 'checking', 'balance' => 1000]);
        Transaction::factory()->create([
            'user_id' => $this->owner->id,
            'wallet_id' => $this->ownerWallet->id,
            'type' => 'expense',
            'status' => 'paid',
            'amount' => 250,
            'description' => 'Despesa Secreta do Dono',
            'date' => now()->toDateString(),
            'is_recurring' => false,
        ]);
    }

    private function professional(): User
    {
        $user = User::factory()->create();
        SubscriptionFactory::new()->for($user)->create(['current_period_end' => now()->addMonth()]);

        return $user->fresh();
    }

    public function test_reports_never_include_another_users_transactions(): void
    {
        $this->actingAs($this->intruder);

        foreach ([[], ['wallet_id' => $this->ownerWallet->id], ['wallet' => $this->ownerWallet->id]] as $query) {
            $this->get(route('reports.index', $query))->assertOk()->assertDontSee('Despesa Secreta do Dono');
            $this->get(route('reports.print', $query))->assertOk()->assertDontSee('Despesa Secreta do Dono');

            $csv = $this->get(route('reports.export', $query));
            $csv->assertOk();
            $this->assertStringNotContainsString('Despesa Secreta do Dono', $csv->streamedContent());
        }

        $this->get(route('reports.pdf', ['wallet_id' => $this->ownerWallet->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_report_email_is_sent_only_to_the_authenticated_user(): void
    {
        Mail::fake();

        $this->actingAs($this->intruder)
            ->post(route('reports.email'), ['wallet_id' => $this->ownerWallet->id])
            ->assertSessionHas('success');

        Mail::assertQueued(FinancialReportMail::class, fn ($mail) => $mail->hasTo($this->intruder->email));
        Mail::assertNotQueued(FinancialReportMail::class, fn ($mail) => $mail->hasTo($this->owner->email));
    }

    public function test_subscription_page_does_not_show_another_users_payments(): void
    {
        PaymentHistory::factory()->for($this->owner)->create(['description' => 'Pagamento Secreto do Dono']);

        $this->actingAs($this->intruder)
            ->get(route('subscription.index'))
            ->assertOk()
            ->assertDontSee('Pagamento Secreto do Dono');
    }

    public function test_saving_ai_credentials_only_touches_own_row(): void
    {
        $ownerCredential = AiCredential::factory()->for($this->owner)->create(['gemini_api_key' => 'owner-key']);

        $this->actingAs($this->intruder)->post(route('ai.credentials.save'), [
            'gemini_api_key' => 'intruder-key',
            'groq_api_key' => null,
            'openrouter_api_key' => null,
            'preferred_provider' => 'gemini',
        ])->assertRedirect(route('ai.settings'));

        $this->assertSame('owner-key', $ownerCredential->fresh()->gemini_api_key);
        $intruderCredential = AiCredential::where('user_id', $this->intruder->id)->firstOrFail();
        $this->assertSame('intruder-key', $intruderCredential->gemini_api_key);
        $this->assertSame(2, AiCredential::count());
    }
}
