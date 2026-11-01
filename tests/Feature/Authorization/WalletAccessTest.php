<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cross-user isolation and shared-wallet membership rules for wallets.
 * Convention: foreign records answer 404 (existence is not disclosed).
 */
class WalletAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $intruder;

    private Wallet $card;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->intruder = User::factory()->create();
        $this->card = Wallet::factory()->for($this->owner)->create([
            'name' => 'Cartao Secreto do Dono',
            'type' => 'credit',
            'balance' => -300,
            'credit_limit' => 5000,
            'closing_day' => 5,
            'due_day' => 12,
        ]);
    }

    public function test_intruder_gets_404_on_every_wallet_route(): void
    {
        $this->actingAs($this->intruder);

        $this->get(route('wallets.show', $this->card))->assertNotFound();
        $this->get(route('wallets.edit', $this->card))->assertNotFound();
        $this->get(route('wallets.invoice', $this->card))->assertNotFound();
        $this->put(route('wallets.update', $this->card), ['name' => 'Invadida'])->assertNotFound();
        $this->delete(route('wallets.destroy', $this->card))->assertNotFound();

        $intruderChecking = Wallet::factory()->for($this->intruder)->create(['type' => 'checking', 'balance' => 1000]);
        $this->post(route('wallets.invoice.pay', $this->card), [
            'source_wallet_id' => $intruderChecking->id,
            'amount' => '100',
            'date' => now()->toDateString(),
            'ref' => now()->format('Y-m'),
        ])->assertNotFound();

        $this->assertDatabaseHas('wallets', ['id' => $this->card->id, 'name' => 'Cartao Secreto do Dono', 'balance' => -300]);
        $this->assertNotSoftDeleted('wallets', ['id' => $this->card->id]);
        $this->assertEquals(1000.00, (float) $intruderChecking->fresh()->balance);
    }

    public function test_foreign_wallet_is_not_listed_on_index(): void
    {
        $this->actingAs($this->intruder)
            ->get(route('wallets.index'))
            ->assertOk()
            ->assertDontSee('Cartao Secreto do Dono');
    }

    public function test_accepted_member_can_view_shared_wallet_but_not_manage_it(): void
    {
        WalletMember::factory()->role('viewer')->create([
            'wallet_id' => $this->card->id,
            'user_id' => $this->intruder->id,
            'accepted_at' => now(),
        ]);

        $this->actingAs($this->intruder);

        $this->get(route('wallets.show', $this->card))
            ->assertOk()
            ->assertSee('Cartao Secreto do Dono')
            ->assertDontSee(route('wallets.edit', $this->card))
            ->assertDontSee(route('wallets.invoice', $this->card));

        $this->get(route('wallets.index'))
            ->assertOk()
            ->assertSee('Carteiras Compartilhadas Comigo')
            ->assertSee('Cartao Secreto do Dono')
            ->assertSee(route('wallets.show', $this->card));

        $this->get(route('wallets.edit', $this->card))->assertNotFound();
        $this->put(route('wallets.update', $this->card), ['name' => 'Invadida'])->assertNotFound();
        $this->delete(route('wallets.destroy', $this->card))->assertNotFound();
        $this->get(route('wallets.invoice', $this->card))->assertNotFound();

        $this->assertDatabaseHas('wallets', ['id' => $this->card->id, 'name' => 'Cartao Secreto do Dono']);
        $this->assertNotSoftDeleted('wallets', ['id' => $this->card->id]);
    }

    public function test_pending_member_and_unrelated_user_cannot_view_shared_wallet(): void
    {
        WalletMember::factory()->pending()->create([
            'wallet_id' => $this->card->id,
            'user_id' => $this->intruder->id,
        ]);
        $stranger = User::factory()->create();

        // A pending member cannot OPEN the wallet...
        $this->actingAs($this->intruder)->get(route('wallets.show', $this->card))->assertNotFound();

        // ...but now sees it listed as a pending invitation awaiting acceptance,
        // and never in the "shared with me" (accepted) section.
        $this->actingAs($this->intruder)->get(route('wallets.index'))
            ->assertOk()
            ->assertSee('Convites pendentes')
            ->assertDontSee('Carteiras Compartilhadas Comigo');

        // An unrelated user sees nothing about the wallet.
        $this->actingAs($stranger)->get(route('wallets.show', $this->card))->assertNotFound();
        $this->actingAs($stranger)->get(route('wallets.index'))->assertDontSee('Cartao Secreto do Dono');
    }

    public function test_owner_still_has_full_access(): void
    {
        $this->actingAs($this->owner);

        $this->get(route('wallets.show', $this->card))->assertOk()->assertSee(route('wallets.edit', $this->card));
        $this->get(route('wallets.edit', $this->card))->assertOk();
        $this->get(route('wallets.invoice', $this->card))->assertOk();
    }

    public function test_cannot_pay_own_invoice_from_another_users_wallet(): void
    {
        $intruderCard = Wallet::factory()->for($this->intruder)->create([
            'type' => 'credit', 'balance' => -100, 'credit_limit' => 1000, 'closing_day' => 5, 'due_day' => 12,
        ]);
        $ownerChecking = Wallet::factory()->for($this->owner)->create(['type' => 'checking', 'balance' => 1000]);

        $this->actingAs($this->intruder)->post(route('wallets.invoice.pay', $intruderCard), [
            'source_wallet_id' => $ownerChecking->id,
            'amount' => '100',
            'date' => now()->toDateString(),
            'ref' => now()->format('Y-m'),
        ])->assertSessionHasErrors('source_wallet_id');

        $this->assertEquals(1000.00, (float) $ownerChecking->fresh()->balance);
    }
}
