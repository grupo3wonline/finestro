<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    /**
     * Routes that should be accessible without terms acceptance.
     */
    private array $except = [
        'terms.accept',
        'terms.show',
        'logout',
        'terms',
        'privacy',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && !$user->terms_accepted_at) {
            $routeName = $request->route()?->getName();

            if (!in_array($routeName, $this->except)) {
                return redirect()->route('terms.accept');
            }
        }

        return $next($request);
    }
}
