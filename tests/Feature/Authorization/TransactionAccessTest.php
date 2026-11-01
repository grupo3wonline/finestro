<?php

namespace Tests\Feature\Authorization;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * User B must not read, change, pay, reopen, delete, restore or download the
 * receipt of user A's transactions. Foreign records answer 404.
 */
class TransactionAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $intruder;

    private Wallet $wallet;

    private Transaction $pending;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        $this->owner = User::factory()->create();
        $this->intruder = User::factory()->create();
        $this->wallet = Wallet::factory()->for($this->owner)->create(['type' => 'checking', 'balance' => 1000]);

        $this->pending = Transaction::factory()->create([
            'user_id' => $this->owner->id,
            'wallet_id' => $this->wallet->id,
            'type' => 'expense',
            'status' => 'pending',
            'amount' => 100,
            'description' => 'Lancamento Secreto do Dono',
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
            'is_recurring' => false,
            'receipt_path' => 'receipts/'.$this->owner->id.'/nota.pdf',
        ]);
        Storage::disk('private')->put($this->pending->receipt_path, 'pdf');
    }

    public function test_intruder_gets_404_on_every_transaction_route(): void
    {
        $this->actingAs($this->intruder);

        $this->get(route('transactions.show', $this->pending))->assertNotFound();
        $this->get(route('transactions.edit', $this->pending))->assertNotFound();
        $this->get(route('transactions.receipt', $this->pending))->assertNotFound();
        $this->put(route('transactions.update', $this->pending), [
            'amount' => '999.00',
            'description' => 'Invadida',
        ])->assertNotFound();
        $this->patch(route('transactions.pay', $this->pending))->assertNotFound();
        $this->patch(route('transactions.unpay', $this->pending))->assertNotFound();
        $this->delete(route('transactions.destroy', $this->pending))->assertNotFound();

        $this->pending->refresh();
        $this->assertSame('pending', $this->pending->status);
        $this->assertEquals('100.00', $this->pending->amount);
        $this->assertSame('Lancamento Secreto do Dono', $this->pending->description);
        $this->assertNotSoftDeleted('transactions', ['id' => $this->pending->id]);
        $this->assertEquals(1000.00, (float) $this->wallet->fresh()->balance);
    }

    public function test_intruder_cannot_restore_a_trashed_transaction(): void
    {
        $this->pending->delete();

        $this->actingAs($this->intruder)
            ->patch(route('transactions.restore', $this->pending->id))
            ->assertNotFound();

        $this->assertSoftDeleted('transactions', ['id' => $this->pending->id]);
    }

    public function test_intruder_cannot_reopen_a_paid_transaction(): void
    {
        $this->pending->forceFill(['status' => 'paid', 'paid_at' => now()])->save();

        $this->actingAs($this->intruder)
            ->patch(route('transactions.unpay', $this->pending))
            ->assertNotFound();

        $this->assertSame('paid', $this->pending->fresh()->status);
    }

    public function test_update_on_foreign_id_does_not_store_the_uploaded_receipt(): void
    {
        $this->actingAs($this->intruder)->put(route('transactions.update', $this->pending), [
            'description' => 'Invadida',
            'receipt' => UploadedFile::fake()->create('ataque.pdf', 10, 'application/pdf'),
        ])->assertNotFound();

        $this->assertSame([], Storage::disk('private')->allFiles('receipts/'.$this->intruder->id));
    }

    public function test_foreign_transactions_are_absent_from_listings(): void
    {
        $this->actingAs($this->intruder);

        $this->get(route('transactions.index', ['month' => 'all']))->assertOk()->assertDontSee('Lancamento Secreto do Dono');
        $this->get(route('bills.index'))->assertOk()->assertDontSee('Lancamento Secreto do Dono');
        $this->get(route('dashboard'))->assertOk()->assertDontSee('Lancamento Secreto do Dono');

        $this->pending->delete();
        $this->get(route('transactions.trash'))->assertOk()->assertDontSee('Lancamento Secreto do Dono');
    }

    public function test_owner_keeps_access_to_own_transaction(): void
    {
        $this->actingAs($this->owner);

        $this->get(route('transactions.show', $this->pending))->assertOk()->assertSee('Lancamento Secreto do Dono');
        $this->get(route('transactions.receipt', $this->pending))->assertOk();
        $this->patch(route('transactions.pay', $this->pending))->assertRedirect();
        $this->assertSame('paid', $this->pending->fresh()->status);
    }
}
