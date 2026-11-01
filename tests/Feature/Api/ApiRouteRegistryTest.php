<?php

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

/*
 * Guard test for the public surface of the REST API v1.
 *
 * It freezes the exact set of registered `api.v1.*` routes (name, method,
 * URI and the token ability each one requires) so that an accidental removal
 * or an unreviewed addition in routes/api.php fails CI instead of silently
 * breaking every client (the whole group once became an empty stub).
 *
 * When you intentionally add or change an endpoint, update EXPECTED here in
 * the same commit — that edit is the review signal.
 */

/**
 * name => [method, uri, ability]
 * `ability` is the token scope enforced by the `abilities:` middleware
 * (null when the route carries no `abilities:` middleware).
 *
 * @var array<string, array{0: string, 1: string, 2: ?string}>
 */
const EXPECTED = [
    'api.v1.auth.me' => ['GET', 'api/v1/auth/me', 'account:read'],
    'api.v1.subscription.show' => ['GET', 'api/v1/subscription', 'account:read'],

    'api.v1.wallets.index' => ['GET', 'api/v1/wallets', 'wallets:read'],
    'api.v1.wallets.store' => ['POST', 'api/v1/wallets', 'wallets:write'],
    'api.v1.wallets.show' => ['GET', 'api/v1/wallets/{wallet}', 'wallets:read'],
    'api.v1.wallets.update' => ['PUT|PATCH', 'api/v1/wallets/{wallet}', 'wallets:write'],
    'api.v1.wallets.destroy' => ['DELETE', 'api/v1/wallets/{wallet}', 'wallets:write'],

    'api.v1.transactions.index' => ['GET', 'api/v1/transactions', 'transactions:read'],
    'api.v1.transactions.store' => ['POST', 'api/v1/transactions', 'transactions:write'],
    'api.v1.transactions.show' => ['GET', 'api/v1/transactions/{transaction}', 'transactions:read'],
    'api.v1.transactions.update' => ['PUT|PATCH', 'api/v1/transactions/{transaction}', 'transactions:write'],
    'api.v1.transactions.destroy' => ['DELETE', 'api/v1/transactions/{transaction}', 'transactions:write'],
    'api.v1.transactions.pay' => ['PATCH', 'api/v1/transactions/{transaction}/pay', 'transactions:write'],
    'api.v1.transactions.unpay' => ['PATCH', 'api/v1/transactions/{transaction}/unpay', 'transactions:write'],
    'api.v1.transactions.receipt' => ['GET', 'api/v1/transactions/{transaction}/receipt', 'transactions:read'],

    'api.v1.goals.index' => ['GET', 'api/v1/goals', 'goals:read'],
    'api.v1.goals.store' => ['POST', 'api/v1/goals', 'goals:write'],
    'api.v1.goals.show' => ['GET', 'api/v1/goals/{goal}', 'goals:read'],
    'api.v1.goals.update' => ['PUT|PATCH', 'api/v1/goals/{goal}', 'goals:write'],
    'api.v1.goals.destroy' => ['DELETE', 'api/v1/goals/{goal}', 'goals:write'],

    'api.v1.budgets.index' => ['GET', 'api/v1/budgets', 'budgets:read'],
    'api.v1.budgets.store' => ['POST', 'api/v1/budgets', 'budgets:write'],
    'api.v1.budgets.update' => ['PUT|PATCH', 'api/v1/budgets/{budget}', 'budgets:write'],
    'api.v1.budgets.destroy' => ['DELETE', 'api/v1/budgets/{budget}', 'budgets:write'],

    'api.v1.categories.index' => ['GET', 'api/v1/categories', 'categories:read'],
    'api.v1.categories.store' => ['POST', 'api/v1/categories', 'categories:write'],
    'api.v1.categories.update' => ['PUT|PATCH', 'api/v1/categories/{category}', 'categories:write'],
    'api.v1.categories.destroy' => ['DELETE', 'api/v1/categories/{category}', 'categories:write'],

    'api.v1.reports.summary' => ['GET', 'api/v1/reports/summary', 'reports:read'],
];

/** The read/write HTTP verbs we care about, ignoring HEAD/OPTIONS Laravel adds. */
function apiV1RouteMethods(RoutingRoute $route): string
{
    $methods = array_values(array_diff($route->methods(), ['HEAD', 'OPTIONS']));

    return implode('|', $methods);
}

/** The ability required by the route's `abilities:<ability>` middleware, if any. */
function apiV1RouteAbility(RoutingRoute $route): ?string
{
    foreach ($route->gatherMiddleware() as $middleware) {
        if (is_string($middleware) && str_starts_with($middleware, 'abilities:')) {
            return substr($middleware, strlen('abilities:'));
        }
    }

    return null;
}

/** @return array<string, array{0: string, 1: string, 2: ?string}> */
function registeredApiV1Routes(): array
{
    $routes = [];

    foreach (Route::getRoutes() as $route) {
        $name = $route->getName();

        if ($name === null || ! str_starts_with($name, 'api.v1.')) {
            continue;
        }

        $routes[$name] = [apiV1RouteMethods($route), $route->uri(), apiV1RouteAbility($route)];
    }

    ksort($routes);

    return $routes;
}

it('registers exactly the expected api.v1 routes with their method, URI and ability', function () {
    $registered = registeredApiV1Routes();
    $expected = EXPECTED;
    ksort($expected);

    expect(array_keys($registered))->toBe(
        array_keys($expected),
        'routes/api.php no longer registers exactly the expected api.v1 route names — update EXPECTED in the same commit.'
    );

    expect($registered)->toEqual($expected);
});

it('guards the whole v1 group behind sanctum, verified, throttle and the plan gate', function () {
    foreach (Route::getRoutes() as $route) {
        if (! str_starts_with((string) $route->getName(), 'api.v1.')) {
            continue;
        }

        expect($route->gatherMiddleware())->toContain('auth:sanctum', 'verified', 'throttle:api', 'api.plan');
    }
});

it('restricts every id parameter to a UUID', function () {
    foreach (Route::getRoutes() as $route) {
        $name = (string) $route->getName();

        if (! str_starts_with($name, 'api.v1.') || ! str_contains($route->uri(), '{')) {
            continue;
        }

        foreach ($route->parameterNames() as $param) {
            // whereUuid() registers Laravel's canonical UUID regex for the parameter.
            expect($route->wheres[$param] ?? null)->toBe(
                '[\da-fA-F]{8}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{12}',
                "Route {$name} does not constrain {{$param}} to a UUID (missing whereUuid)."
            );
        }
    }
});
