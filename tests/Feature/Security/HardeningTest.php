<?php

declare(strict_types=1);

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\QueuedResetPassword;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Security hardening: enumeration, rate limits, CSRF, XSS, SQL injection,
| mass assignment and security headers
|--------------------------------------------------------------------------
|
| Not duplicated here:
| - API per-IP rate limit: tests/Feature/Api/ApiIpRateLimitTest.php
| - IDOR / ownership matrices: tests/Feature/Authorization/* and
|   tests/Feature/DataIsolationTest.php
| - Admin panel CSP and access: tests/Feature/Admin/*
*/

const HARDENING_XSS = '<script>alert(1)</script>';

// Rate-limit counters live in the array cache store (phpunit.xml), which
// starts empty for every test; time is frozen so a minute window cannot roll.
beforeEach(function (): void {
    $this->freezeTime();
});

/**
 * Re-enables CSRF verification, which Laravel skips while running tests.
 * Laravel 12's web group uses ValidateCsrfToken, so that is the class bound
 * here (Laravel 13 renamed it PreventRequestForgery).
 */
function enforceCsrf(): void
{
    app()->bind(ValidateCsrfToken::class, fn (Application $app): ValidateCsrfToken => new class($app, $app['encrypter']) extends ValidateCsrfToken
    {
        protected function runningUnitTests(): bool
        {
            return false;
        }
    });
}

it('answers every failed login with the same generic message', function (): void {
    $ana = User::factory()->create(['username' => 'ana', 'email' => 'ana@example.com']);
    User::factory()->create(['username' => 'bia', 'email' => 'bia@example.com']);

    foreach ([
        'unknown account' => ['username' => 'ninguem', 'email' => 'ninguem@example.com', 'password' => 'password'],
        'wrong password' => ['username' => 'ana', 'email' => 'ana@example.com', 'password' => 'errada'],
        'username/e-mail of different users' => ['username' => 'ana', 'email' => 'bia@example.com', 'password' => 'password'],
    ] as $credentials) {
        // Same key, same text, no field-level hint about which part was wrong.
        $this->from('/login')->post('/login', $credentials)
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['login' => trans('auth.failed')])
            ->assertSessionDoesntHaveErrors(['username', 'email', 'password']);
    }

    $this->assertGuest('web');

    // Sanity check: the right triple still signs in.
    $this->post('/login', ['username' => 'ana', 'email' => 'ana@example.com', 'password' => 'password']);
    $this->assertAuthenticatedAs($ana, 'web');
});

it('answers every password-reset request with the same neutral message', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'ana@example.com']);
    $neutral = 'Se o e-mail estiver cadastrado, você receberá um link de redefinição em instantes.';

    foreach ([
        'unknown e-mail' => 'ninguem@example.com',
        'registered e-mail' => 'ana@example.com',
        // Second request inside the broker's 60 s window: RESET_THROTTLED.
        'throttled by the broker' => 'ana@example.com',
    ] as $email) {
        $this->from('/forgot-password')->post('/forgot-password', ['email' => $email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status', $neutral)
            ->assertSessionHasNoErrors();
    }

    expect(trans('passwords.sent'))->toBe($neutral);
    // Only the first request for the registered account sent a link.
    Notification::assertSentToTimes($user, QueuedResetPassword::class, 1);
});

it('throttles the sixth login attempt for the same account and IP', function (): void {
    $user = User::factory()->create();
    $credentials = ['username' => $user->username, 'email' => $user->email, 'password' => 'errada'];

    foreach (range(1, 5) as $attempt) {
        $this->from('/login')->post('/login', $credentials)->assertSessionHasErrors(['login' => trans('auth.failed')]);
    }

    $this->from('/login')->post('/login', $credentials)
        ->assertSessionHasErrors(['login' => trans('auth.throttle', ['seconds' => 60, 'minutes' => 1])]);
});

it('limits registration and password-reset requests to 6 per minute', function (string $uri, array $payload): void {
    foreach (range(1, 6) as $attempt) {
        expect($this->post($uri, $payload)->getStatusCode())->not->toBe(429);
    }

    $this->post($uri, $payload)->assertStatus(429);
})->with([
    'register' => ['/register', ['name' => 'Bot', 'username' => 'bot', 'email' => 'nao-e-email', 'password' => 'x']],
    'forgot-password' => ['/forgot-password', ['email' => 'ninguem@example.com']],
]);

it('rejects state-changing requests without a CSRF token', function (): void {
    enforceCsrf();

    $this->post('/login', ['username' => 'a', 'email' => 'a@example.com', 'password' => 'x'])->assertStatus(419);
    $this->post(route('consent.store'), ['analytics' => '1', 'marketing' => '0', 'source' => 'banner'])->assertStatus(419);

    $this->actingAs(User::factory()->create())
        ->put(route('settings.password'), [
            'current_password' => 'password',
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ])
        ->assertStatus(419);

    // A cross-site browser request is refused even with the session cookie.
    $this->withHeader('Sec-Fetch-Site', 'cross-site')
        ->post(route('consent.store'), ['analytics' => '1', 'marketing' => '0', 'source' => 'banner'])
        ->assertStatus(419);
});

it('accepts the same request when the CSRF token is present', function (): void {
    enforceCsrf();
    $user = User::factory()->create();

    $this->withSession(['_token' => 'token-valido'])
        ->post('/login', [
            '_token' => 'token-valido',
            'username' => $user->username,
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('dashboard', absolute: false));
});

it('escapes script payloads in wallet names and transaction descriptions', function (): void {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['name' => HARDENING_XSS]);
    Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'description' => HARDENING_XSS,
        'date' => now()->toDateString(),
        'is_recurring' => false,
    ]);

    foreach ([route('wallets.index'), route('transactions.index'), route('dashboard')] as $url) {
        $this->actingAs($user)->get($url)
            ->assertOk()
            ->assertDontSee(HARDENING_XSS, false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
});

it('serves the CSV export as an attachment that no spreadsheet runs as a formula', function (): void {
    $user = proUser();
    $wallet = Wallet::factory()->for($user)->create(['type' => 'checking']);
    Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'status' => 'paid',
        'description' => '=HYPERLINK("https://evil.test","'.HARDENING_XSS.'")',
        'date' => now()->toDateString(),
        'is_recurring' => false,
    ]);

    $response = $this->actingAs($user)->get(route('reports.export'));
    $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

    $csv = $response->streamedContent();
    $cells = collect(explode("\n", substr($csv, 3)))
        ->filter()
        ->flatMap(fn (string $line): array => str_getcsv($line, ';', '"', '\\'));

    // Downloaded as text/csv (never rendered as HTML) and no cell starts with "=".
    expect($response->headers->get('content-type'))->toContain('text/csv')
        ->and($response->headers->get('content-disposition'))->toContain('attachment')
        ->and($cells->filter(fn (?string $cell): bool => str_starts_with((string) $cell, '='))->all())->toBe([])
        ->and($cells->contains(fn (?string $cell): bool => str_starts_with((string) $cell, "'=HYPERLINK")))->toBeTrue();
});

it('treats SQL injection payloads as plain data and shows only own records', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $mine = Wallet::factory()->for($user)->create();
    $theirs = Wallet::factory()->for($other)->create();

    Transaction::factory()->create(['user_id' => $user->id, 'wallet_id' => $mine->id, 'description' => 'Minha despesa', 'date' => now()->toDateString(), 'is_recurring' => false]);
    Transaction::factory()->create(['user_id' => $other->id, 'wallet_id' => $theirs->id, 'description' => 'Despesa alheia', 'date' => now()->toDateString(), 'is_recurring' => false]);

    $usersBefore = User::withTrashed()->count();

    foreach (["' OR 1=1 --", '" OR ""="', '1; DROP TABLE users', "%' UNION SELECT password FROM users --"] as $payload) {
        $this->actingAs($user)->get(route('transactions.index', ['search' => $payload, 'month' => 'all']))
            ->assertOk()
            ->assertDontSee('Despesa alheia');
    }

    // Structured filters are validated at the boundary: rejected, never executed.
    foreach (['type', 'status', 'wallet_id', 'category_id', 'payment_method', 'year'] as $filter) {
        $this->actingAs($user)->from(route('transactions.index'))
            ->get(route('transactions.index', [$filter => '1; DROP TABLE users']))
            ->assertRedirect(route('transactions.index'))
            ->assertSessionHasErrors($filter);
    }

    $this->actingAs($user)->get(route('transactions.index', ['month' => 'all']))
        ->assertOk()
        ->assertSee('Minha despesa')
        ->assertDontSee('Despesa alheia');

    expect(Schema::hasTable('users'))->toBeTrue()
        ->and(User::withTrashed()->count())->toBe($usersBefore);
});

it('ignores privileged attributes sent to registration', function (): void {
    $this->post('/register', [
        'name' => 'Mallory',
        'username' => 'mallory',
        'email' => 'mallory@example.com',
        'password' => 'senha-segura-123',
        'password_confirmation' => 'senha-segura-123',
        'terms' => '1',
        'role' => 'admin',
        'is_admin' => '1',
        'suspended_at' => now()->toDateTimeString(),
        'email_verified_at' => now()->toDateTimeString(),
    ])->assertRedirect();

    $user = User::query()->where('email', 'mallory@example.com')->sole();

    expect($user->hasRole('admin'))->toBeFalse()
        ->and($user->hasRole('user'))->toBeTrue()
        ->and($user->role)->not->toBe('admin')
        ->and($user->suspended_at)->toBeNull()
        ->and($user->email_verified_at)->toBeNull();
});

it('creates a wallet for the signed-in user even when another user_id is posted', function (): void {
    $user = User::factory()->create();
    $victim = User::factory()->create();

    $this->actingAs($user)->post(route('wallets.store'), [
        'name' => 'Carteira forjada',
        'type' => 'checking',
        'ownership' => 'personal',
        'user_id' => $victim->id,
    ])->assertRedirect();

    $wallet = Wallet::withoutGlobalScopes()->where('name', 'Carteira forjada')->sole();
    expect($wallet->user_id)->toBe($user->id);
});

it('sends the security headers on public and authenticated pages', function (?string $as): void {
    $request = $as === 'user' ? $this->actingAs(User::factory()->create()) : $this;
    $response = $request->get($as === 'user' ? route('dashboard') : '/')->assertOk();

    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy');

    $csp = (string) $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain("default-src 'self'")
        ->and($csp)->toContain("frame-ancestors 'self'")
        ->and($csp)->toContain("object-src 'none'");
})->with(['guest' => null, 'user' => 'user']);
