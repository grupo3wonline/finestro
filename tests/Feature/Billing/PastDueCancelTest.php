<?php

use App\Interfaces\Services\StripeGatewayInterface;
use App\Models\Subscription;
use App\Models\User;
use Tests\Fakes\FakeStripeGateway;

/*
 * A past_due subscriber can cancel from Financeiro; the cancellation is
 * immediate because there is no paid period to keep.
 */

beforeEach(function () {
    $this->fake = new FakeStripeGateway;
    $this->app->instance(StripeGatewayInterface::class, $this->fake);

    $this->user = User::factory()->create();
});

function pastDueSubscription(User $user): Subscription
{
    return Subscription::factory()->for($user)->pastDue()->create(['stripe_subscription_id' => 'sub_mine']);
}

it('shows the immediate cancel form to a past_due user', function () {
    pastDueSubscription($this->user);

    $this->actingAs($this->user)->get(route('subscription.index'))
        ->assertOk()
        ->assertSee('Cancelar assinatura')
        ->assertSee('cancelamento é imediato');
});

it('cancels a past_due subscription immediately', function () {
    $subscription = pastDueSubscription($this->user);

    $this->actingAs($this->user)->post(route('subscription.cancel'))
        ->assertRedirect(route('subscription.index'))
        ->assertSessionHas('success', fn (string $message) => str_contains($message, 'Assinatura cancelada'));

    $subscription->refresh();

    expect($this->fake->calls['cancelImmediately'][0][0])->toBe('sub_mine')
        ->and($this->fake->calls)->not->toHaveKey('cancelAtPeriodEnd')
        ->and($subscription->status->value)->toBe('canceled')
        ->and($subscription->cancel_at_period_end)->toBeFalse()
        ->and($subscription->current_period_end->isFuture())->toBeFalse();
});

it('keeps the subscription past_due when Stripe fails', function () {
    $subscription = pastDueSubscription($this->user);
    $this->fake->cancelException = new RuntimeException('stripe down');

    $this->actingAs($this->user)->post(route('subscription.cancel'))
        ->assertRedirect(route('subscription.index'))
        ->assertSessionHas('error');

    expect($subscription->fresh()->status->value)->toBe('past_due');
});

it('still schedules cancellation at period end for an active Pro user', function () {
    Subscription::factory()->for($this->user)->create(['stripe_subscription_id' => 'sub_mine']);

    $this->actingAs($this->user->fresh())->post(route('subscription.cancel'))
        ->assertRedirect(route('subscription.index'))
        ->assertSessionHas('success');

    expect($this->fake->calls['cancelAtPeriodEnd'])->toBe([['sub_mine']])
        ->and($this->fake->calls)->not->toHaveKey('cancelImmediately');
});
