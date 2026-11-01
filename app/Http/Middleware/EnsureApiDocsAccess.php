<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Swagger UI (/api/documentation) and the generated spec: open in the local
 * environment; elsewhere only for session users with role admin. Guests get
 * a 404 (the docs' existence is not advertised), other users a 403.
 */
class EnsureApiDocsAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isLocal() && config('api.docs_public_in_local', true)) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user instanceof User) {
            abort(404);
        }

        abort_unless($user->isAdmin(), 403);

        return $next($request);
    }
}
