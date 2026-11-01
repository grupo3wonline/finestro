<?php

use App\Exceptions\ApiExceptionRenderer;
use App\Exceptions\PlanLimitException;
use App\Http\Middleware\CheckPlanLimit;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureApiAccess;
use App\Http\Middleware\EnsureTermsAccepted;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ThrottleApiByIp;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware - security headers on all responses
        $middleware->append(SecurityHeaders::class);

        // API requests always negotiate JSON.
        $middleware->api(prepend: [ForceJsonResponse::class]);

        // Suspended accounts lose their active web session on the next request.
        $middleware->web(append: [EnsureAccountIsActive::class]);

        // The per-IP API limiter must run before token authentication. Laravel
        // sorts route middleware by this priority list, which would otherwise
        // move auth:sanctum in front of it.
        $middleware->prependToPriorityList(AuthenticatesRequests::class, ThrottleApiByIp::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'plan.limit' => CheckPlanLimit::class,
            'terms.accepted' => EnsureTermsAccepted::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'api.plan' => EnsureApiAccess::class,
            'active' => EnsureAccountIsActive::class,
        ]);

        // API guests get a 401 JSON envelope, never a redirect to the login page.
        $middleware->redirectGuestsTo(fn (Request $request): ?string => $request->is('api/*') ? null : route('login'));

        // The cookie consent must stay readable by resources/js/consent.js.
        $middleware->encryptCookies(except: ['finestro_consent']);

        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Expected, user-facing plan restriction: not an application error.
        $exceptions->dontReport(PlanLimitException::class);

        // Every api/* error uses the {message, code[, errors]} envelope.
        $exceptions->render(fn (Throwable $e, Request $request) => $request->is('api/*')
            ? app(ApiExceptionRenderer::class)->render($e, $request)
            : null);
    })->create();
