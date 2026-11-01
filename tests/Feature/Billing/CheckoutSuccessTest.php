<?php

namespace Tests\Feature\Billing;

use App\Interfaces\Services\StripeGatewayInterface;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeStripeGateway;
use Tests\TestCase;

/**
 * The checkout success page only activates sessions owned by the
 * authenticated user; anything else answers 404 and writes nothing.
 */
class CheckoutSuccessTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeGateway $fake;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fake = new FakeStripeGateway;
        $this->app->instance(StripeGatewayInterface::class, $this->fake);

        $this->user = User::factory()->create();
    }

    private function registerSession(string $id, array $overrides = []): void
    {
        $this->fake->sessions[$id] = FakeStripeGateway::sessionData(array_replace(['id' => $id], $overrides));
    }

    private function visit(?string $sessionId)
    {
        $query = $sessionId === null ? [] : ['session_id' => $sessionId];

        return $this->actingAs($this->user)->get(route('subscription.success', $query));
    }

    public function test_own_session_activates_subscription_and_records_payment(): void
    {
        $this->registerSession('cs_own', ['client_reference_id' => $this->user->id, 'customer_id' => 'cus_own']);

        $this->visit('cs_own')->assertOk()->assertViewIs('subscription.success');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'plan' => 'professional',
            'status' => 'active',
            'stripe_customer_id' => 'cus_own',
            'stripe_subscription_id' => 'sub_fake',
        ]);
        $this->assertDatabaseHas('payment_histories', [
            'user_id' => $this->user->id,
            'stripe_invoice_id' => 'in_fake',
            'amount' => 29.90,
            'status' => 'paid',
        ]);
        $this->assertTrue($this->user->fresh()->isProfessional());
    }

    public function test_foreign_session_returns_404_and_writes_nothing(): void
    {
        $other = User::factory()->create();
        $this->registerSession('cs_foreign', ['client_reference_id' => $other->id]);

        $this->visit('cs_foreign')->assertNotFound();

        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertDatabaseCount('payment_histories', 0);
    }

    public function test_metadata_user_mismatch_returns_404(): void
    {
        $other = User::factory()->create();
        $this->registerSession('cs_meta', [
            'client_reference_id' => $this->user->id,
            'metadata_user_id' => $other->id,
        ]);

        $this->visit('cs_meta')->assertNotFound();

        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertDatabaseCount('payment_histories', 0);
    }

    public function test_customer_mismatch_with_existing_subscription_returns_404(): void
    {
        $existing = Subscription::factory()->for($this->user)->free()->create(['stripe_customer_id' => 'cus_mine']);

        $this->registerSession('cs_cus', ['client_reference_id' => $this->user->id, 'customer_id' => 'cus_someone_else']);

        $this->visit('cs_cus')->assertNotFound();

        $fresh = $existing->fresh();
        $this->assertSame('free', $fresh->plan);
        $this->assertSame('cus_mine', $fresh->stripe_customer_id);
        $this->assertDatabaseCount('payment_histories', 0);
    }

    public function test_unknown_session_returns_404(): void
    {
        $this->visit('cs_does_not_exist')->assertNotFound();

        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_missing_session_id_returns_404(): void
    {
        $this->visit(null)->assertNotFound();

        $this->assertSame([], $this->fake->calls['retrieveCheckoutSession'] ?? []);
    }

    public function test_incomplete_session_returns_404(): void
    {
        $this->registerSession('cs_open', ['client_reference_id' => $this->user->id, 'status' => 'open']);

        $this->visit('cs_open')->assertNotFound();

        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_stores_the_price_id_of_the_session_subscription(): void
    {
        config(['stripe.plans.professional.stripe_price_id' => 'price_from_config']);
        $this->registerSession('cs_price', ['client_reference_id' => $this->user->id, 'price_id' => 'price_on_session']);

        $this->visit('cs_price')->assertOk();

        $this->assertDatabaseHas('subscriptions', ['user_id' => $this->user->id, 'stripe_price_id' => 'price_on_session']);
    }

    public function test_replaying_success_after_subscription_deleted_keeps_user_free(): void
    {
        $this->registerSession('cs_replay', ['client_reference_id' => $this->user->id]);
        $this->visit('cs_replay')->assertOk();
        $this->assertTrue($this->user->fresh()->isProfessional());

        // customer.subscription.deleted (dunning) ended it; Stripe still reports a future period end.
        Subscription::query()->where('user_id', $this->user->id)->update([
            'status' => 'canceled',
            'current_period_end' => now()->subMinute(),
        ]);
        $this->registerSession('cs_replay', [
            'client_reference_id' => $this->user->id,
            'subscription_status' => 'canceled',
            'period_end' => now()->addDays(20),
            'ended_at' => now()->subMinute(),
        ]);

        $this->visit('cs_replay')
            ->assertRedirect(route('subscription.index'))
            ->assertSessionHas('error');

        $fresh = Subscription::query()->where('user_id', $this->user->id)->sole();
        $this->assertSame('canceled', $fresh->status->value);
        $this->assertTrue($fresh->current_period_end->isPast());
        $this->assertFalse($this->user->fresh()->isProfessional());
    }

    public function test_replaying_success_for_a_past_due_subscription_does_not_confirm(): void
    {
        $this->registerSession('cs_pastdue', [
            'client_reference_id' => $this->user->id,
            'subscription_status' => 'past_due',
        ]);

        $this->visit('cs_pastdue')
            ->assertRedirect(route('subscription.index'))
            ->assertSessionHas('error');

        $this->assertFalse($this->user->fresh()->isProfessional());
    }

    public function test_old_ended_session_does_not_overwrite_a_newer_subscription(): void
    {
        Subscription::factory()->for($this->user)->create([
            'stripe_customer_id' => 'cus_fake',
            'stripe_subscription_id' => 'sub_new',
            'status' => 'active',
            'current_period_end' => now()->addDays(25),
        ]);
        $this->registerSession('cs_old', [
            'client_reference_id' => $this->user->id,
            'subscription_id' => 'sub_old',
            'subscription_status' => 'canceled',
            'ended_at' => now()->subMonth(),
        ]);

        $this->visit('cs_old')->assertOk();

        $this->assertDatabaseHas('subscriptions', ['user_id' => $this->user->id, 'stripe_subscription_id' => 'sub_new', 'status' => 'active']);
        $this->assertTrue($this->user->fresh()->isProfessional());
    }

    public function test_reloading_success_page_records_a_single_payment(): void
    {
        $this->registerSession('cs_reload', ['client_reference_id' => $this->user->id]);

        $this->visit('cs_reload')->assertOk();
        $this->visit('cs_reload')->assertOk();

        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertDatabaseCount('payment_histories', 1);
    }
}
