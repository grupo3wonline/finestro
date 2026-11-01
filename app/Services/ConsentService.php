<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ConsentData;
use App\Interfaces\Repositories\CookieConsentRepositoryInterface;
use App\Interfaces\Services\ConsentServiceInterface;
use App\Interfaces\Services\SecurityActivityLoggerInterface;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class ConsentService implements ConsentServiceInterface
{
    public function __construct(
        protected CookieConsentRepositoryInterface $consents,
        protected SecurityActivityLoggerInterface $security,
    ) {}

    public function fromRequest(Request $request): ConsentData
    {
        $version = $this->version();
        $raw = $request->cookie($this->cookieName());

        if (! is_string($raw) || $raw === '') {
            return ConsentData::none($version);
        }

        $data = json_decode($raw, true, 4);

        if (! is_array($data) || ($data['v'] ?? null) !== $version || ! is_int($data['t'] ?? null)) {
            return ConsentData::none($version);
        }

        return new ConsentData(
            version: $version,
            analytics: ($data['a'] ?? 0) === 1,
            marketing: ($data['m'] ?? 0) === 1,
            gpc: ($data['g'] ?? 0) === 1,
            decidedAt: $data['t'],
        );
    }

    public function gpcRequested(Request $request): bool
    {
        return $request->header('Sec-GPC') === '1';
    }

    public function record(Request $request, bool $analytics, bool $marketing, string $source, ?User $user): ConsentData
    {
        $gpc = $this->gpcRequested($request);

        $consent = new ConsentData(
            version: $this->version(),
            analytics: $analytics,
            // CCPA/CPRA: a GPC signal is a valid opt-out of sale/sharing.
            marketing: $marketing && ! $gpc,
            gpc: $gpc,
            decidedAt: now()->getTimestamp(),
        );

        // Not HttpOnly: resources/js/consent.js reads it to load (or not) the tags.
        Cookie::queue(Cookie::make(
            $this->cookieName(),
            $consent->toCookieValue(),
            (int) config('privacy.consent_lifetime_days') * 24 * 60,
            '/',
            null,
            config('session.secure'),
            false,
            false,
            'lax',
        ));

        if ($user instanceof User) {
            $this->consents->record((string) $user->id, $consent, $source);
            $this->security->log('consent_updated', $user, [
                'version' => $consent->version,
                'analytics' => $consent->analytics,
                'marketing' => $consent->marketing,
            ]);
        }

        return $consent;
    }

    public function allows(Request $request, string $category): bool
    {
        $consent = $this->fromRequest($request);

        return $category === 'analytics' ? $consent->analytics : $consent->marketing;
    }

    private function cookieName(): string
    {
        return (string) config('privacy.consent_cookie');
    }

    private function version(): string
    {
        return (string) config('legal.policy_version');
    }
}
