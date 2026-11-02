<?php

declare(strict_types=1);

use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionRenewalNotification;
use Illuminate\Support\Facades\Notification;

/*
|--------------------------------------------------------------------------
| Professional subscription: reminder 7 days before the period ends
|--------------------------------------------------------------------------
*/

function subscriberEndingIn(int $days, array $state = [], array $userState = []): array
{
    $user = User::factory()->create($userState);
    $subscription = Subscription::factory()->for($user)->create([
        'current_period_start' => now()->subMonth()->addDays($days),
        'current_period_end' => now()->addDays($days)->subHour(),
    ] + $state);

    return [$user->fresh(), $subscription];
}

it('defaults to a 7-day warning', function (): void {
    expect(config('security.renewal_warning_days'))->toBe(7);
});

it('shows the renewal banner inside the window', function (): void {
    [$user] = subscriberEndingIn(6);

    $this->actingAs($user)->get('/dashboard')->assertOk()
        ->assertSee('Sua assinatura Profissional renova em 6 dias')
        ->assertSee('Gerenciar assinatura');
});

it('shows the banner on the first day of the window and not before', function (): void {
    [$onTheLimit] = subscriberEndingIn(6);
    [$tooEarly] = subscriberEndingIn(8);

    $this->actingAs($onTheLimit)->get('/dashboard')->assertSee('renova em');
    $this->actingAs($tooEarly)->get('/dashboard')->assertDontSee('Sua assinatura Profissional renova');
});

it('says the subscription ends, with a resume link, when cancellation is scheduled', function (): void {
    [$user] = subscriberEndingIn(3, ['cancel_at_period_end' => true]);

    $this->actingAs($user)->get('/dashboard')->assertOk()
        ->assertSee('Sua assinatura Profissional termina em 3 dias')
        ->assertSee('Retomar assinatura')
        ->assertDontSee('renova em');
});

it('keeps the red banners for past-due and expired subscriptions', function (): void {
    [$pastDue] = subscriberEndingIn(3, ['status' => 'past_due']);
    $this->actingAs($pastDue)->get('/dashboard')->assertSee('Pagamento pendente')->assertDontSee('Sua assinatura Profissional renova');

    $expired = User::factory()->create();
    Subscription::factory()->for($expired)->expired()->create();
    $this->actingAs($expired->fresh())->get('/dashboard')->assertSee('Sua assinatura venceu');
});

it('shows no renewal banner to free users', function (): void {
    $user = User::factory()->create();
    Subscription::factory()->for($user)->free()->create();

    $this->actingAs($user->fresh())->get('/dashboard')->assertOk()->assertDontSee('Sua assinatura Profissional');
});

it('exposes the whole days left on the model', function (): void {
    [, $subscription] = subscriberEndingIn(5);

    expect($subscription->daysUntilPeriodEnd())->toBe(5)
        ->and($subscription->isRenewalDueSoon())->toBeTrue();

    $subscription->current_period_end = null;
    expect($subscription->daysUntilPeriodEnd())->toBeNull()
        ->and($subscription->isRenewalDueSoon())->toBeFalse();
});

it('queues one reminder for each subscriber inside the window', function (): void {
    Notification::fake();

    [$soon] = subscriberEndingIn(5);
    [$far] = subscriberEndingIn(20);
    [$pastDue] = subscriberEndingIn(3, ['status' => 'past_due']);
    [$unverified] = subscriberEndingIn(4, [], ['email_verified_at' => null]);
    [$suspended] = subscriberEndingIn(4, [], ['suspended_at' => now()]);
    $free = User::factory()->create();
    Subscription::factory()->for($free)->free()->create();

    $this->artisan('billing:remind-renewals')->assertSuccessful();

    Notification::assertSentTo($soon, SubscriptionRenewalNotification::class);
    Notification::assertNotSentTo([$far, $pastDue, $unverified, $suspended, $free], SubscriptionRenewalNotification::class);
});

it('sends the reminder once per billing period', function (): void {
    Notification::fake();
    [$user, $subscription] = subscriberEndingIn(5);

    $this->artisan('billing:remind-renewals');
    $this->artisan('billing:remind-renewals');

    Notification::assertSentToTimes($user, SubscriptionRenewalNotification::class, 1);
    expect($subscription->fresh()->renewal_reminded_for->equalTo($subscription->fresh()->current_period_end))->toBeTrue();
});

it('reminds again when Stripe moves the subscription to the next period', function (): void {
    Notification::fake();
    [$user, $subscription] = subscriberEndingIn(5);
    $this->artisan('billing:remind-renewals');

    // Renewed: the period moves a month ahead, out of the window.
    $subscription->update(['current_period_end' => now()->addMonth()]);
    $this->artisan('billing:remind-renewals');
    Notification::assertSentToTimes($user, SubscriptionRenewalNotification::class, 1);

    // A month later it is inside the window again.
    $subscription->update(['current_period_end' => now()->addDays(5)]);
    $this->artisan('billing:remind-renewals');
    Notification::assertSentToTimes($user, SubscriptionRenewalNotification::class, 2);
});

it('writes the renewal e-mail without quoting a price', function (): void {
    [$user, $subscription] = subscriberEndingIn(5);
    $mail = (new SubscriptionRenewalNotification($subscription))->toMail($user);
    $html = (string) $mail->render();

    expect($mail->subject)->toBe('Sua assinatura Profissional renova em 5 dias')
        ->and($html)->toContain('renovada automaticamente')
        ->and($html)->toContain($subscription->current_period_end->format('d/m/Y'))
        ->and($html)->toContain(route('subscription.index'))
        ->and($html)->not->toContain('R$');
});

it('writes the cancellation e-mail when the cancellation is already scheduled', function (): void {
    [$user, $subscription] = subscriberEndingIn(2, ['cancel_at_period_end' => true]);
    $mail = (new SubscriptionRenewalNotification($subscription))->toMail($user);

    expect($mail->subject)->toBe('Sua assinatura Profissional termina em 2 dias')
        ->and((string) $mail->render())->toContain('Retomar assinatura')->toContain('volta ao plano Básico');
});
