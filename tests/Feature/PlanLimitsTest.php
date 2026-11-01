<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies freemium enforcement: free users are capped (1 wallet) while
 * professional subscribers are not, and gated features respond correctly.
 */
class PlanLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_user_reports_free_plan_and_limits(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->isFree());
        $this->assertFalse($user->isProfessional());
        $this->assertSame(1, $user->plan_limits['wallets']);
        $this->assertFalse($user->hasFeature('simulators'));
    }

    public function test_professional_subscriber_reports_professional_plan(): void
    {
        $user = User::factory()->create();
        Subscription::factory()->for($user)->create();

        $user->refresh()->load('subscription');

        $this->assertTrue($user->isProfessional());
        $this->assertTrue($user->hasFeature('simulators'));
        $this->assertSame(PHP_INT_MAX, $user->plan_limits['wallets']);
    }

    public function test_free_user_can_create_first_wallet_but_not_second(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->canCreate('wallet'));

        Wallet::factory()->for($user)->create();

        $this->assertFalse($user->fresh()->canCreate('wallet'));
    }

    public function test_free_user_blocked_from_creating_second_wallet_via_http(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('wallets.store'), [
            'name' => 'Segunda Carteira',
            'type' => 'checking',
            'ownership' => 'personal',
            'currency' => 'BRL',
        ]);

        $response->assertRedirect(route('wallets.index'));
        $response->assertSessionHas('error');

        // Only the original wallet should exist.
        $this->assertSame(1, $user->wallets()->count());
    }

    public function test_professional_user_can_create_multiple_wallets(): void
    {
        $user = User::factory()->create();
        Subscription::factory()->for($user)->create();
        $user->refresh();

        Wallet::factory()->for($user)->count(3)->create();

        $this->assertTrue($user->fresh()->canCreate('wallet'));
    }
}
