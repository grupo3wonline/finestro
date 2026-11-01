<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\TransactionHistory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Contas a pagar/receber, parcelamento, auditoria, lixeira e comprovante.
 */
class TransactionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Wallet $checking;

    private Wallet $card;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->checking = Wallet::factory()->for($this->user)->create(['type' => 'checking', 'balance' => 1000]);
        $this->card = Wallet::factory()->for($this->user)->create([
            'type' => 'credit', 'balance' => 0, 'credit_limit' => 5000, 'closing_day' => 5, 'due_day' => 12,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'wallet_id' => $this->checking->id,
            'type' => 'expense',
            'amount' => '100.00',
            'description' => 'Conta de teste',
            'date' => now()->toDateString(),
        ], $overrides);
    }

    public function test_paid_transaction_changes_balance_and_pending_does_not(): void
    {
        $this->actingAs($this->user)->post(route('transactions.store'), $this->payload(['status' => 'paid']))->assertRedirect();
        $this->assertEquals(900.00, (float) $this->checking->fresh()->balance);

        $this->actingAs($this->user)->post(route('transactions.store'), $this->payload(['status' => 'pending', 'amount' => '50']))->assertRedirect();
        $this->assertEquals(900.00, (float) $this->checking->fresh()->balance, 'Conta pendente não pode afetar o saldo');
    }

    public function test_mark_as_paid_and_reopen_adjusts_balance_and_records_history(): void
    {
        $this->actingAs($this->user)->post(route('transactions.store'), $this->payload(['status' => 'pending']));
        $transaction = Transaction::firstOrFail();
        $this->assertNull($transaction->paid_at);

        $this->actingAs($this->user)->patch(route('transactions.pay', $transaction))->assertRedirect();
        $transaction->refresh();
        $this->assertTrue($transaction->isPaid());
        $this->assertNotNull($transaction->paid_at);
        $this->assertEquals(900.00, (float) $this->checking->fresh()->balance);

        $this->actingAs($this->user)->patch(route('transactions.unpay', $transaction))->assertRedirect();
        $this->assertTrue($transaction->fresh()->isPending());
        $this->assertNull($transaction->fresh()->paid_at);
        $this->assertEquals(1000.00, (float) $this->checking->fresh()->balance);

        $this->assertEqualsCanonicalizing(
            ['created', 'paid', 'unpaid'],
            TransactionHistory::where('transaction_id', $transaction->id)->pluck('action')->all()
        );
    }

    public function test_installments_split_cents_without_losing_money(): void
    {
        $this->actingAs($this->user)->post(route('transactions.store'), $this->payload([
            'amount' => '100.00', 'installments' => 3, 'date' => '2026-01-31', 'status' => 'paid',
        ]))->assertRedirect();

        $installments = Transaction::orderBy('installment_number')->get();

        $this->assertCount(3, $installments);
        $this->assertEquals(['33.33', '33.33', '33.34'], $installments->pluck('amount')->all());
        $this->assertEquals(100.00, (float) $installments->sum('amount'));
        // Datas mensais sem "overflow" (31/01 → 28/02 → 31/03).
        $this->assertEquals(['2026-01-31', '2026-02-28', '2026-03-31'], $installments->map(fn ($t) => $t->date->toDateString())->all());
        $this->assertEquals(1, $installments->pluck('installment_group_id')->unique()->count());

        // Carteira comum: 1ª paga, demais pendentes → só a 1ª afeta o saldo.
        $this->assertEquals(['paid', 'pending', 'pending'], $installments->pluck('status')->all());
        $this->assertEquals(966.67, (float) $this->checking->fresh()->balance);
    }

    public function test_credit_card_installments_consume_full_limit_immediately(): void
    {
        $this->actingAs($this->user)->post(route('transactions.store'), $this->payload([
            'wallet_id' => $this->card->id, 'amount' => '1200', 'installments' => 12,
        ]))->assertRedirect();

        $this->assertEquals(12, Transaction::where('status', 'paid')->count());
        $card = $this->card->fresh();
        $this->assertEquals(-1200.00, (float) $card->balance);
        $this->assertEquals(1200.00, $card->credit_used);
        $this->assertEquals(3800.00, $card->available_credit);
        $this->assertEquals(24.0, $card->credit_usage_percent);
    }

    public function test_installments_and_recurring_are_mutually_exclusive(): void
    {
        $this->actingAs($this->user)
            ->post(route('transactions.store'), $this->payload(['installments' => 3, 'is_recurring' => 1, 'recurring_frequency' => 'monthly']))
            ->assertSessionHasErrors('installments');
    }

    public function test_update_records_diff_with_reason_and_rebalances_wallet(): void
    {
        $this->actingAs($this->user)->post(route('transactions.store'), $this->payload());
        $transaction = Transaction::firstOrFail();

        $this->actingAs($this->user)->put(route('transactions.update', $transaction), [
            'amount' => '150.00',
            'description' => 'Conta corrigida',
            'reason' => 'Valor conforme nota fiscal',
            '_has_recurring_field' => 1,
        ])->assertRedirect(route('transactions.show', $transaction));

        $this->assertEquals(850.00, (float) $this->checking->fresh()->balance);

        $history = TransactionHistory::where('action', 'updated')->firstOrFail();
        $this->assertEquals('Valor conforme nota fiscal', $history->reason);
        $this->assertEquals(['old' => '100.00', 'new' => '150.00'], $history->changes['amount']);
        $this->assertEquals('Conta corrigida', $history->changes['description']['new']);
    }

    public function test_optional_fields_can_be_cleared_and_recurrence_disabled_on_edit(): void
    {
        $this->actingAs($this->user)->post(route('transactions.store'), $this->payload([
            'notes' => 'Anotação', 'is_recurring' => 1, 'recurring_frequency' => 'monthly',
        ]));
        $transaction = Transaction::firstOrFail();
        $this->assertTrue($transaction->is_recurring);

        // Checkbox desmarcado não é enviado pelo navegador; campo de texto vazio vira null.
        $this->actingAs($this->user)->put(route('transactions.update', $transaction), [
            'notes' => '',
            '_has_recurring_field' => 1,
        ])->assertSessionHasNoErrors();

        $transaction->refresh();
        $this->assertFalse($transaction->is_recurring);
        $this->assertNull($transaction->notes);
    }

    public function test_delete_goes_to_trash_and_restore_reapplies_balance(): void
    {
        $this->actingAs($this->user)->post(route('transactions.store'), $this->payload());
        $transaction = Transaction::firstOrFail();

        $this->actingAs($this->user)->delete(route('transactions.destroy', $transaction), ['reason' => 'Duplicada'])->assertRedirect();
        $this->assertSoftDeleted($transaction);
        $this->assertEquals(1000.00, (float) $this->checking->fresh()->balance);
        $this->assertDatabaseHas('transaction_histories', ['transaction_id' => $transaction->id, 'action' => 'deleted', 'reason' => 'Duplicada']);

        $this->actingAs($this->user)->get(route('transactions.trash'))->assertOk()->assertSee('Conta de teste');

        $this->actingAs($this->user)->patch(route('transactions.restore', $transaction->id))->assertRedirect();
        $this->assertNotSoftDeleted($transaction);
        $this->assertEquals(900.00, (float) $this->checking->fresh()->balance);
    }

    public function test_delete_whole_installment_group(): void
    {
        $this->actingAs($this->user)->post(route('transactions.store'), $this->payload([
            'wallet_id' => $this->card->id, 'amount' => '300', 'installments' => 3,
        ]));
        $first = Transaction::orderBy('installment_number')->firstOrFail();

        $this->actingAs($this->user)->delete(route('transactions.destroy', $first), ['scope' => 'installments'])->assertRedirect();

        $this->assertEquals(0, Transaction::count());
        $this->assertEquals(3, Transaction::onlyTrashed()->count());
        $this->assertEquals(0.00, (float) $this->card->fresh()->balance);
    }

    public function test_cannot_post_transaction_to_another_users_wallet(): void
    {
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->post(route('transactions.store'), $this->payload())
            ->assertSessionHasErrors('wallet_id');

        $this->assertEquals(0, Transaction::withoutGlobalScopes()->count());
        $this->assertEquals(1000.00, (float) $this->checking->fresh()->balance);
    }

    public function test_receipt_is_stored_privately_and_only_owner_can_download(): void
    {
        Storage::fake('private');

        $this->actingAs($this->user)->post(route('transactions.store'), $this->payload([
            // Real PDF bytes: the media library checks the content type.
            'receipt' => UploadedFile::fake()->createWithContent('nota.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF"),
        ]))->assertSessionHasNoErrors();

        $transaction = Transaction::firstOrFail();
        $media = $transaction->getFirstMedia('receipt');
        $this->assertNotNull($media);
        $this->assertSame('private', $media->disk);
        Storage::disk('private')->assertExists($media->getPathRelativeToRoot());
        $this->assertNull($transaction->receipt_path, 'New uploads no longer write receipt_path');

        $this->actingAs($this->user)->get(route('transactions.receipt', $transaction))->assertOk();

        $other = User::factory()->create();
        $this->actingAs($other)->get(route('transactions.receipt', $transaction))->assertNotFound();
    }

    public function test_brazilian_amount_format_is_accepted(): void
    {
        $this->actingAs($this->user)->post(route('transactions.store'), $this->payload(['amount' => '1.234,56']))
            ->assertSessionHasNoErrors();

        $this->assertEquals('1234.56', Transaction::firstOrFail()->amount);
    }
}
