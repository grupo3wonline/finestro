<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Interfaces\Services\WalletSharingServiceInterface;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

class ClaimWalletInvitations
{
    public function __construct(
        private readonly WalletSharingServiceInterface $sharingService,
    ) {}

    /**
     * Claim any pending wallet invitations addressed to the user's email,
     * but only after the email address has been verified. This prevents a
     * user from inheriting invitations for an address they do not own.
     */
    public function handle(Verified $event): void
    {
        // Verified carries the MustVerifyEmail contract; the app's only
        // implementation is App\Models\User.
        if ($event->user instanceof User) {
            $this->sharingService->claimInvitationsForUser($event->user);
        }
    }
}
