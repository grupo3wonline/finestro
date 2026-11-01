<?php

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;

/*
 * API v1 foundation: token-only auth, plan gate, abilities, JSON error
 * envelope, rate limit (per user), CORS and the account endpoints.
 */

function assertApiError(TestResponse $response, int $status, string $code): TestResponse
{
    $response->assertStatus($status)
        ->assertJsonPath('code', $code)
        ->assertJsonStructure(['message', 'code']);

    expect($response->json('message'))->toBeString()->not->toBe('');

    return $response;
}

it('answers 401 with the envelope without a token', function () {
    $response = assertApiError($this->getJson('/api/v1/auth/me'), 401, 'unauthenticated');

    expect($response->json('message'))->toContain('Authorization: Bearer');
});

it('answers 401 even when the client asks for HTML', function () {
    assertApiError($this->get('/api/v1/auth/me', ['Accept' => 'text/html']), 401, 'unauthenticated')
        ->assertHeader('Content-Type', 'application/json');
});

it('answers 401 for a malformed token', function () {
    assertApiError($this->withToken('not-a-real-token')->getJson('/api/v1/auth/me'), 401, 'unauthenticated');
    assertApiError($this->withToken('999|abcdef')->getJson('/api/v1/auth/me'), 401, 'unauthenticated');
});

it('answers 401 for an expired token', function () {
    $token = apiToken(proUser());

    $this->travel((int) config('sanctum.expiration') + 1)->minutes();

    assertApiError($this->withToken($token)->getJson('/api/v1/auth/me'), 401, 'unauthenticated');
});

it('is token-only: a logged-in web session without a token gets 401', function () {
    $this->actingAs(proUser());

    assertApiError($this->getJson('/api/v1/auth/me'), 401, 'unauthenticated');
});

it('answers 403 plan_upgrade_required for Free users', function () {
    $response = assertApiError(
        $this->withToken(apiToken(freeUser()))->getJson('/api/v1/auth/me'),
        403,
        'plan_upgrade_required'
    );

    expect($response->json('message'))->toContain('plano Profissional');
});

it('answers 403 plan_upgrade_required for a downgraded user holding an old token', function () {
    $user = proUser();
    $token = apiToken($user);
    $user->subscription->update(['current_period_end' => now()->subDay()]);

    assertApiError($this->withToken($token)->getJson('/api/v1/auth/me'), 403, 'plan_upgrade_required');
});

it('answers 403 email not verified for an unverified account', function () {
    $user = User::factory()->unverified()->create();
    Subscription::factory()->for($user)->create();

    $response = assertApiError($this->withToken(apiToken($user))->getJson('/api/v1/auth/me'), 403, 'http_error');

    expect($response->json('message'))->toBe('Confirme seu e-mail para usar a API.');
});

it('answers 403 missing_ability when the token lacks the ability', function () {
    assertApiError(
        $this->withToken(apiToken(proUser(), ['wallets:read']))->getJson('/api/v1/auth/me'),
        403,
        'missing_ability'
    )->assertJsonPath('abilities', ['account:read']);
});

it('returns the account with an exact key set and no secrets', function () {
    $user = proUser();
    $token = apiToken($user, ['account:read', 'wallets:read']);

    $response = $this->withToken($token)->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.plan', 'professional')
        ->assertJsonPath('data.plan_name', 'Profissional')
        ->assertJsonPath('data.features.api_access', true)
        ->assertJsonPath('data.limits.wallets', null)
        ->assertJsonPath('data.token.name', 'Pest')
        ->assertJsonPath('data.token.abilities', ['account:read', 'wallets:read']);

    expect(array_keys($response->json('data')))->toEqualCanonicalizing([
        'id', 'name', 'username', 'email', 'email_verified_at', 'plan', 'plan_name', 'features', 'limits', 'token',
    ])
        ->and(array_keys($response->json('data.features')))->toEqualCanonicalizing([
            'simulators', 'reports_export', 'ai_insights', 'wallet_sharing', 'api_access',
        ])
        ->and(array_keys($response->json('data.limits')))->toEqualCanonicalizing([
            'wallets', 'transactions_per_month', 'goals', 'categories', 'budgets_per_month',
        ]);

    $body = $response->getContent();
    expect($body)->not->toContain('password')
        ->not->toContain('remember_token')
        ->not->toContain('"role"')
        ->not->toContain('stripe');
});

it('returns the subscription status without Stripe identifiers', function () {
    $user = proUser();

    $response = $this->withToken(apiToken($user, ['account:read']))->getJson('/api/v1/subscription')
        ->assertOk()
        ->assertJsonPath('data.plan', 'professional')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.is_active', true);

    expect(array_keys($response->json('data')))->toEqualCanonicalizing([
        'plan', 'status', 'status_label', 'is_active', 'cancel_at_period_end', 'current_period_start',
        'current_period_end', 'days_remaining', 'canceled_at',
    ])
        ->and($response->getContent())->not->toContain('stripe_')
        ->not->toContain($user->subscription->stripe_subscription_id)
        ->not->toContain($user->subscription->stripe_customer_id);
});

it('answers 404 with the envelope on an unknown endpoint', function () {
    assertApiError($this->withToken(apiToken(proUser()))->getJson('/api/v1/nope'), 404, 'not_found')
        ->assertJsonPath('message', 'Recurso não encontrado.');
});

it('answers 404 for a non-UUID id before any query', function () {
    assertApiError($this->withToken(apiToken(proUser()))->getJson('/api/v1/wallets/123'), 404, 'not_found');
});

it('answers 405 with the envelope on a wrong method', function () {
    assertApiError($this->withToken(apiToken(proUser()))->deleteJson('/api/v1/auth/me'), 405, 'http_error')
        ->assertJsonPath('message', 'Método não permitido.');
});

it('answers 429 with Retry-After over the limit', function () {
    config(['api.rate_limit_per_minute' => 2]);
    $token = apiToken(proUser());

    $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertHeader('X-RateLimit-Limit', '2');
    $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk();

    assertApiError($this->withToken($token)->getJson('/api/v1/auth/me'), 429, 'too_many_requests')
        ->assertHeader('Retry-After');
});

it('counts the rate limit per user across all of their tokens', function () {
    config(['api.rate_limit_per_minute' => 2]);
    $user = proUser();
    $first = apiToken($user);
    $second = apiToken($user);
    $other = apiToken(proUser());

    $this->withToken($first)->getJson('/api/v1/auth/me')->assertOk();
    app('auth')->forgetGuards();
    $this->withToken($second)->getJson('/api/v1/auth/me')->assertOk();
    app('auth')->forgetGuards();
    assertApiError($this->withToken($second)->getJson('/api/v1/auth/me'), 429, 'too_many_requests');

    // Another user has an independent quota.
    app('auth')->forgetGuards();
    $this->withToken($other)->getJson('/api/v1/auth/me')->assertOk();
});

it('renders a 500 envelope without trace or exception details when debug is off', function () {
    Exceptions::fake();
    config(['app.debug' => false]);

    Route::middleware(['api', 'auth:sanctum'])
        ->get('api/v1/_boom', fn () => throw new RuntimeException('segredo interno'));

    $response = assertApiError($this->withToken(apiToken(proUser()))->getJson('/api/v1/_boom'), 500, 'server_error')
        ->assertJsonMissingPath('trace')
        ->assertJsonMissingPath('exception')
        ->assertJsonMissingPath('file');

    expect($response->getContent())->not->toContain('segredo interno');
    Exceptions::assertReported(RuntimeException::class);
});

it('restricts CORS to the configured origins without credentials', function () {
    $allowed = (string) config('app.url');

    expect(config('cors.allowed_origins'))->toBe([$allowed])
        ->and(config('cors.supports_credentials'))->toBeFalse();

    $preflight = fn (string $origin): TestResponse => $this->call('OPTIONS', '/api/v1/auth/me', [], [], [], [
        'HTTP_ORIGIN' => $origin,
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization',
    ]);

    // With a single allowed origin, fruitcake/php-cors always answers that
    // origin, so a foreign origin is never echoed back (the browser blocks it).
    $foreign = $preflight('https://evil.example');
    expect($foreign->headers->get('Access-Control-Allow-Origin'))->not->toBe('https://evil.example')
        ->not->toBe('*');

    $ok = $preflight($allowed)->assertHeader('Access-Control-Allow-Origin', $allowed);
    expect($ok->headers->get('Access-Control-Allow-Credentials'))->not->toBe('true');
});
