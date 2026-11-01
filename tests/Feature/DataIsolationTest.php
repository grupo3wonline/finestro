<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the micro SaaS core promise: every user may only ever read or
 * mutate their own records. Exercises the BelongsToUser global scope through
 * the real HTTP routes so a regression in scoping fails loudly here.
 */
class DataIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_index_only_shows_own_wallets(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        Wallet::factory()->for($owner)->create(['name' => 'Minha Carteira']);
        Wallet::factory()->for($other)->create(['name' => 'Carteira Alheia']);

        $response = $this->actingAs($owner)->get(route('wallets.index'));

        $response->assertOk();
        $response->assertSee('Minha Carteira');
        $response->assertDontSee('Carteira Alheia');
    }

    public function test_user_cannot_view_another_users_wallet(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $wallet = Wallet::factory()->for($owner)->create();

        $response = $this->actingAs($intruder)->get(route('wallets.show', $wallet));

        // Global scope hides the record, so route-model binding yields 404.
        $response->assertNotFound();
    }

    public function test_user_cannot_update_another_users_wallet(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $wallet = Wallet::factory()->for($owner)->create(['name' => 'Original']);

        $response = $this->actingAs($intruder)->put(route('wallets.update', $wallet), [
            'name' => 'Invadida',
            'type' => 'checking',
            'ownership' => 'personal',
            'currency' => 'BRL',
        ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'name' => 'Original',
        ]);
    }

    public function test_user_cannot_delete_another_users_wallet(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $wallet = Wallet::factory()->for($owner)->create();

        $response = $this->actingAs($intruder)->delete(route('wallets.destroy', $wallet));

        $response->assertNotFound();
        $this->assertNotSoftDeleted('wallets', ['id' => $wallet->id]);
    }

    public function test_transaction_queries_are_scoped_to_the_authenticated_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $ownerWallet = Wallet::factory()->for($owner)->create();
        $otherWallet = Wallet::factory()->for($other)->create();

        Transaction::factory()->count(3)->create([
            'user_id' => $owner->id,
            'wallet_id' => $ownerWallet->id,
        ]);
        Transaction::factory()->count(5)->create([
            'user_id' => $other->id,
            'wallet_id' => $otherWallet->id,
        ]);

        $this->actingAs($owner);

        $this->assertSame(3, Transaction::query()->count());
    }
}
