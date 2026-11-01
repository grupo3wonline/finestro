<?php

declare(strict_types=1);

use App\Enums\DataExportStatus;
use App\Interfaces\Services\DataExportServiceInterface;
use App\Interfaces\Services\StripeGatewayInterface;
use App\Models\DataExport;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\QueuedResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\Fakes\FakeStripeGateway;

/*
|--------------------------------------------------------------------------
| End-to-end user journeys across several routes
|--------------------------------------------------------------------------
|
| Each test chains the requests a real person makes, so a regression in
| the hand-off between two screens (redirect targets, gates, session state)
| is caught even when the single-route tests stay green.
*/

/**
 * @return array<string, string>
 */
function journeyCredentials(User $user, string $password = 'password'): array
{
    return ['username' => $user->username, 'email' => $user->email, 'password' => $password];
}

it('registers, verifies the e-mail, signs out and signs back in with username + e-mail + password', function (): void {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Joana Jornada',
        'username' => 'joana_jornada',
        'email' => 'joana@example.com',
        'password' => 'senha-segura-123',
        'password_confirmation' => 'senha-segura-123',
        'terms' => '1',
        'website' => '', // honeypot stays empty for humans
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'joana@example.com')->sole();
    expect($user->hasVerifiedEmail())->toBeFalse()
        ->and($user->terms_version)->toBe(config('legal.policy_version'));

    // Unverified accounts are held at the verification notice.
    $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));

    $verifyUrl = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);
    // Back to the page that asked for verification (the intended URL).
    $this->get($verifyUrl)->assertRedirect(route('dashboard'));
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    // Terms were accepted at sign-up in the current version: no detour.
    $this->get(route('dashboard'))->assertOk();

    $this->post(route('logout'))->assertRedirect();
    $this->assertGuest('web');

    $this->post('/login', journeyCredentials($user, 'senha-segura-123'))
        ->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user->fresh(), 'web');
    $this->get(route('dashboard'))->assertOk();
});

it('resets a forgotten password end to end', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');

    $token = null;
    Notification::assertSentTo($user, QueuedResetPassword::class, function (QueuedResetPassword $notification) use (&$token): bool {
        $token = $notification->token;

        return true;
    });

    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'nova-senha-456',
        'password_confirmation' => 'nova-senha-456',
    ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

    // The old password no longer works; the new one does.
    $this->from('/login')->post('/login', journeyCredentials($user))
        ->assertSessionHasErrors(['login' => trans('auth.failed')]);
    $this->assertGuest('web');

    $this->post('/login', journeyCredentials($user, 'nova-senha-456'))
        ->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user->fresh(), 'web');
});

it('creates a wallet, schedules a bill, pays it and exports the report as Profissional', function (): void {
    $user = proUser();

    $this->actingAs($user)->post(route('wallets.store'), [
        'name' => 'Conta da Jornada',
        'type' => 'checking',
        'ownership' => 'personal',
        'balance' => '1000',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $wallet = Wallet::query()->where('name', 'Conta da Jornada')->sole();
    expect($wallet->user_id)->toBe($user->id);

    $this->post(route('transactions.store'), [
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'amount' => '250,00',
        'description' => 'Aluguel da jornada',
        'date' => now()->toDateString(),
        'status' => 'pending',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $transaction = Transaction::query()->where('description', 'Aluguel da jornada')->sole();
    expect($transaction->isPending())->toBeTrue()
        ->and((float) $wallet->fresh()->balance)->toBe(1000.0);

    $this->patch(route('transactions.pay', $transaction))->assertRedirect();
    expect($transaction->fresh()->isPaid())->toBeTrue()
        ->and((float) $wallet->fresh()->balance)->toBe(750.0);

    $csv = $this->get(route('reports.export'));
    $csv->assertOk();
    expect($csv->headers->get('content-type'))->toContain('text/csv')
        ->and($csv->streamedContent())->toContain('Aluguel da jornada');

    $xlsx = $this->get(route('reports.export', ['format' => 'xlsx']));
    $xlsx->assertOk();
    expect($xlsx->headers->get('content-type'))->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('upgrades a Free account through checkout and schedules the cancellation', function (): void {
    $fake = new FakeStripeGateway;
    $this->app->instance(StripeGatewayInterface::class, $fake);
    $user = freeUser();

    expect($user->isProfessional())->toBeFalse();

    $this->actingAs($user)->post(route('subscription.checkout'))->assertRedirect($fake->checkoutUrl);

    // Stripe redirects back to the success URL with the session id.
    $fake->sessions['cs_journey'] = FakeStripeGateway::sessionData([
        'id' => 'cs_journey',
        'client_reference_id' => $user->id,
        'subscription_id' => 'sub_journey',
    ]);
    $this->get(route('subscription.success', ['session_id' => 'cs_journey']))->assertOk();
    expect($user->fresh()->isProfessional())->toBeTrue();

    $this->get(route('subscription.index'))->assertOk();

    $this->post(route('subscription.cancel'))
        ->assertRedirect(route('subscription.index'))
        ->assertSessionHas('success');

    expect($user->fresh()->subscription()->first()->cancel_at_period_end)->toBeTrue()
        ->and($user->fresh()->isProfessional())->toBeTrue()
        ->and($fake->calls['cancelAtPeriodEnd'])->toBe([['sub_journey']]);
});

it('loads analytics only after the visitor consents', function (): void {
    config([
        'services.google.gtm_id' => 'GTM-JOURNEY',
        'services.google.analytics_id' => 'G-JOURNEY',
        'services.facebook.pixel_id' => '999',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('googletagmanager.com', false)
        ->assertDontSee('google-analytics.com', false)
        ->assertDontSee('connect.facebook.net', false);

    $response = $this->postJson(route('consent.store'), ['analytics' => true, 'marketing' => false, 'source' => 'banner'])
        ->assertOk()
        ->assertJson(['analytics' => true, 'marketing' => false]);

    $cookie = collect($response->headers->getCookies())
        ->first(fn (Cookie $cookie): bool => $cookie->getName() === 'finestro_consent');
    expect($cookie)->not->toBeNull();

    $this->withUnencryptedCookie('finestro_consent', (string) $cookie->getValue())
        ->get('/')
        ->assertOk()
        ->assertSee('googletagmanager.com/ns.html?id=GTM-JOURNEY', false);
});

it('exports personal data, then deletes the account and cannot sign in again', function (): void {
    Storage::fake('local');
    $user = freeUser();

    $this->actingAs($user)->post(route('settings.privacy.export'))->assertRedirect(route('settings.privacy'));

    $export = DataExport::withoutGlobalScopes()->where('user_id', $user->id)->sole();
    expect($export->status)->toBe(DataExportStatus::Ready);

    $this->get(app(DataExportServiceInterface::class)->downloadUrl($export))->assertOk()->assertDownload();

    $this->delete(route('settings.account.delete'), ['password' => 'password'])
        ->assertRedirect(route('home'))
        ->assertSessionHas('success');

    $this->assertGuest('web');
    $this->assertSoftDeleted($user);

    $this->from('/login')->post('/login', journeyCredentials($user))
        ->assertSessionHasErrors(['login' => trans('auth.failed')]);
    $this->assertGuest('web');
});
