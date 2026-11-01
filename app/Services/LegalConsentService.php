<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\Services\LegalConsentServiceInterface;
use App\Interfaces\Services\SecurityActivityLoggerInterface;
use App\Models\User;

class LegalConsentService implements LegalConsentServiceInterface
{
    public function __construct(
        private readonly SecurityActivityLoggerInterface $securityLogger,
    ) {}

    public function needsAcceptance(User $user): bool
    {
        return $user->terms_accepted_at === null
            || $user->terms_version !== $this->currentVersion();
    }

    public function acceptTerms(User $user): void
    {
        $version = $this->currentVersion();

        $user->update([
            'terms_accepted_at' => now(),
            'terms_version' => $version,
        ]);

        $this->securityLogger->log('terms_accepted', $user, ['version' => $version]);
    }

    public function currentVersion(): string
    {
        return (string) config('legal.policy_version');
    }
}
