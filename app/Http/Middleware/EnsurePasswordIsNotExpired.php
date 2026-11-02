<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Password aging: a password older than config('security.password_max_age_days')
 * (90 days) locks the app until the user sets a new one. Only the forced change
 * screen and logout stay reachable (they live outside the groups that use this).
 */
class EnsurePasswordIsNotExpired
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->isPasswordExpired()) {
            return redirect()->route('password.expired');
        }

        return $next($request);
    }
}
