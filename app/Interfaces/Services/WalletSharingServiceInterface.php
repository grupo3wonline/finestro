<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Database\Eloquent\Collection;

interface WalletSharingServiceInterface
{
    /**
     * Invite a user (by email) to share a wallet.
     *
     * Creates a pending WalletMember (accepted_at = null). The invited user
     * must call accept() before gaining read access.
     *
     * @param  Wallet  $wallet  The wallet being shared (owned by $inviter).
     * @param  User  $inviter  The authenticated owner issuing the invite.
     * @param  string  $email  Email of the user being invited.
     * @param  string  $role  Member role: 'viewer' | 'editor' | 'admin'.
     */
    public function invite(Wallet $wallet, User $inviter, string $email, string $role): WalletMember;

    /**
     * Accept a pending invitation, setting accepted_at = now().
     */
    public function accept(WalletMember $member, User $user): WalletMember;

    /**
     * Decline a pending invitation or leave/remove a membership.
     */
    public function revoke(WalletMember $member): bool;

    /**
     * List all memberships (pending and accepted) for a wallet.
     *
     * @return Collection<int, WalletMember>
     */
    public function membersOf(Wallet $wallet): Collection;

    /**
     * Pending invitations awaiting the given user's acceptance.
     *
     * @return Collection<int, WalletMember>
     */
    public function pendingInvitationsFor(User $user): Collection;

    /**
     * Attach pending unclaimed (email-based) invitations to a user whose email
     * matches. Returns the number of invitations claimed.
     */
    public function claimInvitationsForUser(User $user): int;
}
