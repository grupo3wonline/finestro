<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\Models\User;

interface LegalConsentServiceInterface
{
    /**
     * Whether the user has never accepted the legal documents or accepted
     * a version other than the current one.
     */
    public function needsAcceptance(User $user): bool;

    /**
     * Record acceptance of the current policy version.
     */
    public function acceptTerms(User $user): void;

    /**
     * Current Terms of Use / Privacy Policy version (config/legal.php).
     */
    public function currentVersion(): string;
}
