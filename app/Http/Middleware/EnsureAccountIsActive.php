<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends access for suspended accounts outside the admin panel.
 *
 * Web: logs out the active session and redirects to the login page with the
 * suspension notice. API: 403 `forbidden` envelope with the same message
 * (rendered by ApiExceptionRenderer).
 */
class EnsureAccountIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     *
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isSuspended()) {
            return $next($request);
        }

        if ($request->is('api/*')) {
            throw new AuthorizationException(User::SUSPENDED_MESSAGE);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['login' => User::SUSPENDED_MESSAGE]);
    }
}
