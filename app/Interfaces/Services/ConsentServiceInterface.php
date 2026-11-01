<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\DTOs\ConsentData;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Cookie consent (LGPD art. 8, GDPR art. 7, ePrivacy, CCPA/CPRA opt-out).
 *
 * Categories: necessary (always on), analytics, marketing. A decision is
 * valid only for the current policy version (config legal.policy_version).
 */
interface ConsentServiceInterface
{
    /**
     * The visitor's decision read from the consent cookie; ConsentData::none()
     * when missing, malformed or for another policy version.
     */
    public function fromRequest(Request $request): ConsentData;

    /**
     * Whether the browser sent the Global Privacy Control signal (Sec-GPC: 1).
     */
    public function gpcRequested(Request $request): bool;

    /**
     * Store a decision: queues the first-party cookie for everyone and, for a
     * logged-in user, records a cookie_consents row and a `consent_updated`
     * activity. GPC forces marketing off.
     */
    public function record(Request $request, bool $analytics, bool $marketing, string $source, ?User $user): ConsentData;

    /**
     * @param  'analytics'|'marketing'  $category
     */
    public function allows(Request $request, string $category): bool;
}
