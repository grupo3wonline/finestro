<?php

use App\Interfaces\Services\StripeGatewayInterface;
use App\Models\User;
use Tests\Fakes\FakeStripeGateway;

/*
 * The success page confirms only a subscription that grants Pro access;
 * every other Stripe status goes back to Financeiro with its own message.
 */

beforeEach(function () {
    $this->fake = new FakeStripeGateway;
    $this->app->instance(StripeGatewayInterface::class, $this->fake);

    $this->user = User::factory()->create();
});

function visitSuccess(object $test, string $status)
{
    $test->fake->sessions['cs_x'] = FakeStripeGateway::sessionData([
        'id' => 'cs_x',
        'client_reference_id' => $test->user->id,
        'subscription_status' => $status,
    ]);

    return $test->actingAs($test->user)->get(route('subscription.success', ['session_id' => 'cs_x']));
}

it('redirects non-Pro results to Financeiro with a per-status message', function (string $status, string $expected) {
    visitSuccess($this, $status)->assertRedirect(route('subscription.index'));

    expect(session('error'))->toContain($expected)
        ->and($this->user->fresh()->isProfessional())->toBeFalse();
})->with([
    'past_due' => ['past_due', 'Pagamento pendente'],
    'unpaid' => ['unpaid', 'Pagamento pendente'],
    'incomplete' => ['incomplete', 'Pagamento ainda não confirmado'],
    // StripePayloadMapper maps incomplete_expired to canceled: the subscription is over.
    'incomplete_expired' => ['incomplete_expired', 'Assine novamente'],
    'canceled' => ['canceled', 'Assine novamente'],
]);

it('shows the confirmation page for an active subscription', function () {
    visitSuccess($this, 'active')->assertOk()->assertViewIs('subscription.success');

    expect($this->user->fresh()->isProfessional())->toBeTrue();
});
