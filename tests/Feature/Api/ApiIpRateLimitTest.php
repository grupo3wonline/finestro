<?php

use App\Http\Middleware\ThrottleApiByIp;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

/*
 * Per-IP API limiter (`api-ip`) that runs before auth:sanctum, so requests
 * without a token or with an invalid token are capped as well.
 */

beforeEach(function (): void {
    config(['api.ip_rate_limit_per_minute' => 3]);
});

it('answers 429 to unauthenticated requests over the IP limit', function (): void {
    foreach (range(1, 3) as $ignored) {
        $this->getJson('/api/v1/wallets')->assertUnauthorized();
    }

    $this->getJson('/api/v1/wallets')
        ->assertStatus(429)
        ->assertJsonPath('code', 'too_many_requests')
        ->assertJsonStructure(['message', 'code'])
        ->assertHeader('Retry-After');
});

it('counts requests with a malformed token against the IP limit', function (): void {
    $this->withToken('not-a-real-token')->getJson('/api/v1/wallets')->assertUnauthorized();
    $this->withToken('999|abcdef')->getJson('/api/v1/auth/me')->assertUnauthorized();
    $this->getJson('/api/v1/goals')->assertUnauthorized();

    $this->withToken('not-a-real-token')->getJson('/api/v1/wallets')->assertStatus(429);
});

it('keys the IP limit by client address', function (): void {
    foreach (range(1, 3) as $ignored) {
        $this->getJson('/api/v1/wallets')->assertUnauthorized();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
        ->getJson('/api/v1/wallets')
        ->assertUnauthorized();
});

it('runs the IP limiter before authentication on every api.v1 route', function (): void {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route): bool => str_starts_with((string) $route->getName(), 'api.v1.'));

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        $declared = $route->gatherMiddleware();
        expect(array_search(ThrottleApiByIp::class, $declared, true))
            ->toBeLessThan(array_search('auth:sanctum', $declared, true));

        // The router sorts middleware by priority before running them.
        $sorted = array_values(app('router')->gatherRouteMiddleware($route));
        $ipIndex = array_search(ThrottleApiByIp::class, $sorted, true);
        $authIndex = collect($sorted)->search(
            fn (mixed $middleware): bool => is_string($middleware) && str_starts_with($middleware, Authenticate::class)
        );

        expect($ipIndex)->toBeInt()
            ->and($authIndex)->toBeInt()
            ->and($ipIndex)->toBeLessThan($authIndex);
    }
});
