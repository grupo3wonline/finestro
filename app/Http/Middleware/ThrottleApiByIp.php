<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-IP API rate limit (named limiter `api-ip`) that runs BEFORE auth:sanctum,
 * so unauthenticated and invalid-token requests are capped too.
 *
 * Laravel sorts route middleware by its priority list, which puts
 * AuthenticatesRequests before ThrottleRequests (and its subclasses) and
 * moves listed middleware ahead of unlisted ones. So this class wraps
 * ThrottleRequests by composition (it is not matched by the ThrottleRequests
 * entry) and bootstrap/app.php prepends it to the priority list right before
 * AuthenticatesRequests.
 */
class ThrottleApiByIp
{
    public function __construct(
        private readonly ThrottleRequests $throttle
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $this->throttle->handle($request, $next, 'api-ip');
    }
}
