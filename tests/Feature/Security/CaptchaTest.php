<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Bot protection: Google reCAPTCHA v3 + Cloudflare Turnstile
|--------------------------------------------------------------------------
|
| The provider endpoints are always faked; no test reaches the network.
| phpunit.xml empties the keys, so each test turns the providers on itself.
*/

/**
 * @param  array<string, string>  $extra
 * @return array<string, string>
 */
function captchaPayload(User $user, array $extra = []): array
{
    return ['username' => $user->username, 'email' => $user->email, 'password' => 'password'] + $extra;
}

/**
 * Http::fake() keeps the first matching stub, so start from a clean factory
 * whenever a test changes what the provider answers.
 *
 * @param  array<string, mixed>  $stubs
 */
function fakeCaptchaProviders(array $stubs): void
{
    app()->forgetInstance(HttpFactory::class);
    Http::clearResolvedInstance(HttpFactory::class);
    Http::fake($stubs);
}

function enableRecaptcha(): void
{
    config(['captcha.recaptcha.site_key' => 'site-r', 'captcha.recaptcha.secret_key' => 'secret-r']);
}

function enableTurnstile(): void
{
    config(['captcha.turnstile.site_key' => 'site-t', 'captcha.turnstile.secret_key' => 'secret-t']);
}

it('does not require a token while no provider is configured', function (): void {
    $user = User::factory()->create();

    $this->post('/login', captchaPayload($user))->assertRedirect(route('dashboard', absolute: false));
});

it('only counts a provider as enabled when both of its keys are set', function (): void {
    config(['captcha.recaptcha.site_key' => 'site-only', 'captcha.turnstile.secret_key' => 'secret-only']);

    $user = User::factory()->create();

    $this->post('/login', captchaPayload($user))->assertRedirect(route('dashboard', absolute: false));
});

it('rejects a login without a reCAPTCHA token and never calls Google', function (): void {
    enableRecaptcha();
    fakeCaptchaProviders([]);
    $user = User::factory()->create();

    $this->from('/login')->post('/login', captchaPayload($user))
        ->assertSessionHasErrors('g-recaptcha-response');

    Http::assertNothingSent();
    $this->assertGuest('web');
});

it('rejects reCAPTCHA tokens with the wrong action, a low score or a provider error', function (): void {
    enableRecaptcha();
    $user = User::factory()->create();

    fakeCaptchaProviders(['www.google.com/*' => Http::sequence()
        ->push(['success' => true, 'score' => 0.9, 'action' => 'register'])
        ->push(['success' => true, 'score' => 0.1, 'action' => 'login'])
        ->push(['success' => false, 'error-codes' => ['invalid-input-response']])
        ->push('boom', 500)]);

    foreach (range(1, 4) as $attempt) {
        $this->from('/login')->post('/login', captchaPayload($user, ['g-recaptcha-response' => 'token']))
            ->assertSessionHasErrors('g-recaptcha-response');
    }

    $this->assertGuest('web');
});

it('fails closed when the provider is unreachable', function (): void {
    enableTurnstile();
    $user = User::factory()->create();

    fakeCaptchaProviders(['challenges.cloudflare.com/*' => fn () => throw new RuntimeException('timeout')]);

    $this->from('/login')->post('/login', captchaPayload($user, ['cf-turnstile-response' => 'token']))
        ->assertSessionHasErrors('cf-turnstile-response');

    $this->assertGuest('web');
});

it('accepts a good reCAPTCHA score and sends the secret, token and IP to Google', function (): void {
    enableRecaptcha();
    $user = User::factory()->create();

    fakeCaptchaProviders(['www.google.com/*' => Http::response(['success' => true, 'score' => 0.9, 'action' => 'login'])]);

    $this->post('/login', captchaPayload($user, ['g-recaptcha-response' => 'good-token']))
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user, 'web');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://www.google.com/recaptcha/api/siteverify'
        && $request['secret'] === 'secret-r'
        && $request['response'] === 'good-token'
        && filled($request['remoteip']));
});

it('honours RECAPTCHA_MIN_SCORE', function (): void {
    enableRecaptcha();
    config(['captcha.recaptcha.min_score' => 0.8]);
    $user = User::factory()->create();

    fakeCaptchaProviders(['www.google.com/*' => Http::response(['success' => true, 'score' => 0.7, 'action' => 'login'])]);

    $this->from('/login')->post('/login', captchaPayload($user, ['g-recaptcha-response' => 'token']))
        ->assertSessionHasErrors('g-recaptcha-response');
});

it('requires both providers to pass when both are enabled', function (): void {
    enableRecaptcha();
    enableTurnstile();
    $user = User::factory()->create();
    $tokens = ['g-recaptcha-response' => 'r', 'cf-turnstile-response' => 't'];

    fakeCaptchaProviders([
        'www.google.com/*' => Http::response(['success' => true, 'score' => 0.9, 'action' => 'login']),
        'challenges.cloudflare.com/*' => Http::response(['success' => false]),
    ]);
    $this->from('/login')->post('/login', captchaPayload($user, $tokens))
        ->assertSessionHasErrors('cf-turnstile-response')
        ->assertSessionDoesntHaveErrors('g-recaptcha-response');
    $this->assertGuest('web');

    fakeCaptchaProviders([
        'www.google.com/*' => Http::response(['success' => true, 'score' => 0.9, 'action' => 'login']),
        'challenges.cloudflare.com/*' => Http::response(['success' => true]),
    ]);
    $this->post('/login', captchaPayload($user, $tokens))->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user, 'web');
});

it('does not count a captcha failure as a failed login attempt', function (): void {
    enableTurnstile();
    $user = User::factory()->create();
    fakeCaptchaProviders(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

    foreach (range(1, 6) as $attempt) {
        $this->from('/login')->post('/login', captchaPayload($user, ['cf-turnstile-response' => 'x']))
            ->assertSessionHasErrors('cf-turnstile-response');
    }

    // Six rejected captchas must not have locked the real credentials out.
    fakeCaptchaProviders(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
    $this->post('/login', captchaPayload($user, ['cf-turnstile-response' => 'x']))
        ->assertRedirect(route('dashboard', absolute: false));
});

it('protects registration, forgot password, reset password and resend confirmation', function (): void {
    enableTurnstile();
    fakeCaptchaProviders([]);

    $this->from('/register')->post('/register', [
        'name' => 'Bot', 'username' => 'botuser', 'email' => 'bot@example.com',
        'password' => 'password', 'password_confirmation' => 'password', 'terms' => '1',
    ])->assertSessionHasErrors('cf-turnstile-response');
    expect(User::query()->where('email', 'bot@example.com')->exists())->toBeFalse();

    $this->from('/forgot-password')->post('/forgot-password', ['email' => 'bot@example.com'])
        ->assertSessionHasErrors('cf-turnstile-response');

    $this->from('/reset-password/token')->post('/reset-password', [
        'token' => 'token', 'email' => 'bot@example.com',
        'password' => 'password1', 'password_confirmation' => 'password1',
    ])->assertSessionHasErrors('cf-turnstile-response');

    $this->from('/reenviar-confirmacao')->post('/reenviar-confirmacao', ['email' => 'bot@example.com'])
        ->assertSessionHasErrors('cf-turnstile-response');

    Http::assertNothingSent();
});

it('renders the widgets on every guest form only when the providers are configured', function (): void {
    $forms = ['/login', '/register', '/forgot-password', '/reenviar-confirmacao'];

    foreach ($forms as $url) {
        $this->get($url)->assertOk()
            ->assertDontSee('cf-turnstile', false)
            ->assertDontSee('recaptcha/api.js', false);
    }

    config([
        'captcha.recaptcha.site_key' => 'RECAPTCHA-SITE', 'captcha.recaptcha.secret_key' => 'secret-r',
        'captcha.turnstile.site_key' => 'TURNSTILE-SITE', 'captcha.turnstile.secret_key' => 'secret-t',
    ]);

    foreach ($forms as $url) {
        $this->get($url)->assertOk()
            ->assertSee('class="cf-turnstile"', false)
            ->assertSee('data-sitekey="TURNSTILE-SITE"', false)
            ->assertSee('recaptcha/api.js?render=RECAPTCHA-SITE', false)
            ->assertSee('data-recaptcha-action', false)
            // The secret keys never reach the page.
            ->assertDontSee('secret-r', false)
            ->assertDontSee('secret-t', false);
    }
});

it('labels each form with its own reCAPTCHA action', function (): void {
    enableRecaptcha();

    foreach (['/login' => 'login', '/register' => 'register', '/forgot-password' => 'forgot_password', '/reenviar-confirmacao' => 'resend_verification'] as $url => $action) {
        $this->get($url)->assertSee('data-recaptcha-action="'.$action.'"', false);
    }
});

it('allows the provider origins in the Content-Security-Policy', function (): void {
    $csp = (string) $this->get('/login')->headers->get('Content-Security-Policy');

    expect($csp)
        ->toContain('https://challenges.cloudflare.com')
        ->toContain('https://www.google.com/recaptcha/')
        ->toContain('https://www.gstatic.com/recaptcha/');
});

it('lists the configured captcha providers in the privacy policy', function (): void {
    $this->get(route('privacy'))->assertOk()->assertDontSee('Proteção contra robôs');

    enableRecaptcha();
    enableTurnstile();

    $this->get(route('privacy'))->assertOk()
        ->assertSee('Proteção contra robôs')
        ->assertSee('Google reCAPTCHA e Cloudflare Turnstile');
});
