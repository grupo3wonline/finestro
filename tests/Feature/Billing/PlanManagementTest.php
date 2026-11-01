<?php

namespace Tests\Feature\Billing;

use App\Interfaces\Services\StripeGatewayInterface;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeStripeGateway;
use Tests\TestCase;

/**
 * Plan management: checkout, cancel at period end and resume act only on
 * the authenticated user's own subscription, through the faked gateway.
 */
class PlanManagementTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeGateway $fake;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fake = new FakeStripeGateway;
        $this->app->instance(StripeGatewayInterface::class, $this->fake);
    }

    private function proUser(array $attributes = [], ?string $state = null): User
    {
        $user = User::factory()->create();
        $factory = Subscription::factory()->for($user);

        if ($state) {
            $factory = $factory->{$state}();
        }

        $factory->create(array_replace(['stripe_subscription_id' => 'sub_mine'], $attributes));

        return $user->fresh();
    }

    public function test_free_user_checkout_redirects_to_stripe_with_regular_price(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('subscription.checkout'))
            ->assertRedirect($this->fake->checkoutUrl);

        $call = $this->fake->calls['createCheckoutSession'][0];
        $this->assertSame('price_test_professional', $call[1]);
        $this->assertSame($user->id, $call[2]);
    }

    public function test_checkout_uses_promo_price_id_when_promo_is_active(): void
    {
        config(['stripe.promo' => [
            'enabled' => true,
            'price' => '19.90',
            'ends_at' => now()->addDay()->toIso8601String(),
            'stripe_price_id' => 'price_test_promo',
            'label' => 'Oferta especial',
        ]]);

        $this->actingAs(User::factory()->create())->post(route('subscription.checkout'))
            ->assertRedirect($this->fake->checkoutUrl);

        $this->assertSame('price_test_promo', $this->fake->calls['createCheckoutSession'][0][1]);
    }

    public function test_professional_user_cannot_checkout_again(): void
    {
        $this->actingAs($this->proUser())->post(route('subscription.checkout'))
            ->assertRedirect(route('subscription.index'))
            ->assertSessionHas('info');

        $this->assertArrayNotHasKey('createCheckoutSession', $this->fake->calls);
        $this->assertArrayNotHasKey('ensureCustomer', $this->fake->calls);
    }

    public function test_cancel_schedules_cancellation_at_period_end_and_keeps_pro(): void
    {
        $user = $this->proUser();

        $this->actingAs($user)->post(route('subscription.cancel'))
            ->assertRedirect(route('subscription.index'))
            ->assertSessionHas('success');

        $subscription = $user->subscription()->first();
        $this->assertTrue($subscription->cancel_at_period_end);
        $this->assertSame('active', $subscription->status->value);
        $this->assertTrue($user->fresh()->isProfessional());
        $this->assertSame([['sub_mine']], $this->fake->calls['cancelAtPeriodEnd']);
    }

    public function test_resume_clears_pending_cancellation(): void
    {
        $user = $this->proUser([], 'pendingCancellation');

        $this->actingAs($user)->post(route('subscription.resume'))
            ->assertRedirect(route('subscription.index'))
            ->assertSessionHas('success');

        $subscription = $user->subscription()->first();
        $this->assertFalse($subscription->cancel_at_period_end);
        $this->assertNull($subscription->canceled_at);
        $this->assertSame([['sub_mine']], $this->fake->calls['resumeSubscription']);
    }

    public function test_resume_without_pending_cancellation_flashes_error(): void
    {
        $this->actingAs($this->proUser())->post(route('subscription.resume'))
            ->assertRedirect(route('subscription.index'))
            ->assertSessionHas('error');

        $this->assertArrayNotHasKey('resumeSubscription', $this->fake->calls);
    }

    public function test_cancel_twice_flashes_error(): void
    {
        $this->actingAs($this->proUser([], 'pendingCancellation'))->post(route('subscription.cancel'))
            ->assertRedirect(route('subscription.index'))
            ->assertSessionHas('error');

        $this->assertArrayNotHasKey('cancelAtPeriodEnd', $this->fake->calls);
    }

    public function test_free_user_cancel_flashes_error(): void
    {
        $this->actingAs(User::factory()->create())->post(route('subscription.cancel'))
            ->assertRedirect(route('subscription.index'))
            ->assertSessionHas('error');

        $this->assertArrayNotHasKey('cancelAtPeriodEnd', $this->fake->calls);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('subscription.resume'))->assertRedirect(route('login'));
        $this->post(route('subscription.cancel'))->assertRedirect(route('login'));
        $this->post(route('subscription.checkout'))->assertRedirect(route('login'));
    }
}
