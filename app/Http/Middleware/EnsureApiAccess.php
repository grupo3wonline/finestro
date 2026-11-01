<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\PlanLimitException;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The REST API is a Professional plan feature (`api_access`). Also blocks
 * users who were downgraded while still holding old tokens.
 */
class EnsureApiAccess
{
    public const string MESSAGE = 'O acesso à API está disponível no plano Profissional. Faça upgrade para usar seus tokens.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->hasFeature('api_access')) {
            throw new PlanLimitException(self::MESSAGE, 'plan_upgrade_required');
        }

        return $next($request);
    }
}
