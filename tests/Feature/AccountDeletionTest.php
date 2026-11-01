<?php

use App\Enums\SubscriptionStatus;
use App\Exceptions\StripeResourceMissingException;
use App\Interfaces\Services\AccountDeletionServiceInterface;
use App\Interfaces\Services\StripeGatewayInterface;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use Stripe\Exception\InvalidRequestException;
use Tests\Fakes\FakeStripeGateway;

/*
 * Both deletion endpoints cancel an open Stripe subscription before
 * deleting the account, and keep the account when Stripe fails.
 */

beforeEach(function () {
    $this->fake = new FakeStripeGateway;
    $this->app->instance(StripeGatewayInterface::class, $this->fake);

    $this->user = User::factory()->create();
});

dataset('deletion endpoints', [
    'settings' => ['settings.account.delete'],
    'profile' => ['profile.destroy'],
]);

it('cancels the Stripe subscription and deletes a Pro account', function (string $route) {
    Subscription::factory()->for($this->user)->create(['stripe_subscription_id' => 'sub_mine']);

    $this->actingAs($this->user->fresh())->delete(route($route), ['password' => 'password'])
        ->assertRedirect();

    expect($this->fake->calls['cancelImmediately'][0][0])->toBe('sub_mine');
    $this->assertSoftDeleted($this->user);
    $this->assertGuest();
})->with('deletion endpoints');

it('keeps the account when the Stripe cancellation fails', function (string $route) {
    Log::spy();
    Subscription::factory()->for($this->user)->create(['stripe_subscription_id' => 'sub_mine']);
    $this->fake->cancelException = new RuntimeException('stripe down');

    $this->actingAs($this->user->fresh())->delete(route($route), ['password' => 'password'])
        ->assertRedirect()
        ->assertSessionHas('error', fn (string $message) => str_contains($message, 'não foi excluída'));

    $this->assertNotSoftDeleted($this->user);
    $this->assertAuthenticated();
    Log::shouldHaveReceived('error')->once();
})->with('deletion endpoints');

it('deletes the account when Stripe no longer has the subscription', function (string $route) {
    $subscription = Subscription::factory()->for($this->user)->create(['stripe_subscription_id' => 'sub_gone']);
    $this->fake->cancelException = new StripeResourceMissingException('gone');

    $this->actingAs($this->user->fresh())->delete(route($route), ['password' => 'password'])
        ->assertRedirect();

    $this->assertSoftDeleted($this->user);
    $this->assertGuest();
    expect(Subscription::withTrashed()->find($subscription->id)->status)->toBe(SubscriptionStatus::CANCELED);
})->with('deletion endpoints');

it('keeps the account when Stripe reports a live/test mode mismatch', function (string $route) {
    $subscription = Subscription::factory()->for($this->user)->create(['stripe_subscription_id' => 'sub_live']);
    $this->fake->cancelException = InvalidRequestException::factory(
        "No such subscription: 'sub_live'; a similar object exists in live mode, but a test mode key was used to make this request.",
        404, null, null, null, 'resource_missing',
    );

    $this->actingAs($this->user->fresh())->delete(route($route), ['password' => 'password'])
        ->assertRedirect()
        ->assertSessionHas('error', fn (string $message) => str_contains($message, 'não foi excluída'));

    $this->assertNotSoftDeleted($this->user);
    expect($subscription->fresh()->status)->not->toBe(SubscriptionStatus::CANCELED);
})->with('deletion endpoints');

it('logs the already-gone shortcut at error level and still deletes', function (string $route) {
    Log::spy();
    Subscription::factory()->for($this->user)->create(['stripe_subscription_id' => 'sub_gone']);
    $this->fake->cancelException = new StripeResourceMissingException('gone');

    $this->actingAs($this->user->fresh())->delete(route($route), ['password' => 'password'])
        ->assertRedirect();

    Log::shouldHaveReceived('error')->withArgs(
        fn (string $message) => str_contains($message, 'already gone')
    )->once();
    $this->assertSoftDeleted($this->user);
})->with('deletion endpoints');

it('deletes a Free account without calling Stripe', function (string $route) {
    $this->actingAs($this->user)->delete(route($route), ['password' => 'password'])
        ->assertRedirect();

    expect($this->fake->calls)->not->toHaveKey('cancelImmediately');
    $this->assertSoftDeleted($this->user);
})->with('deletion endpoints');

it('revokes the API tokens of a deleted account', function (string $route) {
    $this->user->createToken('integracao', ['wallets:read']);
    $other = User::factory()->create();
    $other->createToken('outro', ['wallets:read']);

    $this->actingAs($this->user)->delete(route($route), ['password' => 'password'])
        ->assertRedirect();

    $this->assertSoftDeleted($this->user);
    $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $this->user->id]);
    $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $other->id]);
})->with('deletion endpoints');

it('records the acting admin as causer of the account deletion', function () {
    $admin = User::factory()->admin()->create();

    app(AccountDeletionServiceInterface::class)->delete($this->user, $admin);

    $activity = Activity::query()->where('log_name', 'security')->where('event', 'account_deleted')->latest('id')->firstOrFail();

    $this->assertSoftDeleted($this->user);
    expect($activity->causer_id)->toBe($admin->id)
        ->and($activity->subject_id)->toBe($this->user->id);
});

it('does not call Stripe for an already canceled subscription', function (string $route) {
    Subscription::factory()->for($this->user)->canceled()->create(['stripe_subscription_id' => 'sub_mine']);

    $this->actingAs($this->user->fresh())->delete(route($route), ['password' => 'password'])
        ->assertRedirect();

    expect($this->fake->calls)->not->toHaveKey('cancelImmediately');
    $this->assertSoftDeleted($this->user);
})->with('deletion endpoints');
