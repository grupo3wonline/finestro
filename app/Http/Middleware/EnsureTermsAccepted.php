<?php

namespace App\Http\Middleware;

use App\Interfaces\Services\LegalConsentServiceInterface;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    /**
     * Routes that should be accessible without terms acceptance.
     *
     * The data subject rights routes (privacy page, export request, export
     * download, account deletion) stay reachable so exercising LGPD art. 18 /
     * GDPR arts. 15, 17 and 20 never depends on accepting a new policy
     * version. Their own safeguards (auth, verified, password confirmation,
     * signed links, throttles, owner scoping) still apply.
     *
     * @var list<string>
     */
    private array $except = [
        'terms.accept',
        'terms.show',
        'logout',
        'terms',
        'privacy',
        'settings.privacy',
        'settings.privacy.export',
        'settings.privacy.download',
        'settings.account.delete',
    ];

    public function __construct(
        private readonly LegalConsentServiceInterface $legalConsent,
    ) {}

    /**
     * Handle an incoming request.
     *
     * Redirects users who never accepted the legal documents, or accepted an
     * older config('legal.policy_version'), to the acceptance page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user instanceof User && $this->legalConsent->needsAcceptance($user)) {
            $routeName = $request->route()?->getName();

            if (! in_array($routeName, $this->except)) {
                return redirect()->route('terms.accept');
            }
        }

        return $next($request);
    }
}
