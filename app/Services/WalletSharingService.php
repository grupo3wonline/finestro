<?php

namespace App\Services;

use App\Interfaces\Services\WalletSharingServiceInterface;
use App\Mail\WalletInvitationMail;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Handles the wallet-sharing lifecycle: invite → accept/decline → revoke.
 *
 * Plan gating (hasFeature('wallet_sharing')) is enforced at the controller;
 * ownership/authorization is enforced via WalletPolicy. The wallet's
 * `is_shared` flag is kept in sync with its accepted-member count.
 */
class WalletSharingService implements WalletSharingServiceInterface
{
    /**
     * Human-readable (pt-BR) labels for the membership roles.
     */
    private const array ROLE_LABELS = [
        'viewer' => 'Apenas visualizar',
        'editor' => 'Visualizar e editar',
        'admin' => 'Administrar',
    ];

    /**
     * Unclaimed invitations stay valid for this many days.
     */
    private const int INVITE_TTL_DAYS = 14;

    public function invite(Wallet $wallet, User $inviter, string $email, string $role): WalletMember
    {
        $email = mb_strtolower(trim($email));

        if (mb_strtolower($inviter->email) === $email) {
            throw ValidationException::withMessages([
                'email' => 'Você já é o dono desta carteira.',
            ]);
        }

        $invitee = User::where('email', $email)->first();

        return $invitee
            ? $this->inviteRegistered($wallet, $inviter, $invitee, $role)
            : $this->inviteUnregistered($wallet, $inviter, $email, $role);
    }

    /**
     * Invite someone who already has a Finestro account.
     */
    private function inviteRegistered(Wallet $wallet, User $inviter, User $invitee, string $role): WalletMember
    {
        if ($wallet->members()->where('user_id', $invitee->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Este usuário já foi convidado para esta carteira.',
            ]);
        }

        $member = $wallet->members()->create([
            'user_id' => $invitee->id,
            'email' => $invitee->email,
            'role' => $role,
            'accepted_at' => null,
        ]);

        $this->sendInvitationEmail(
            $invitee->name,
            $invitee->email,
            $inviter->name,
            $wallet->name,
            $role,
            route('wallets.index'),
        );

        return $member;
    }

    /**
     * Invite someone who does not have an account yet: create an unclaimed,
     * token-based invite and email a registration link.
     */
    private function inviteUnregistered(Wallet $wallet, User $inviter, string $email, string $role): WalletMember
    {
        // Lock the existing-invite check + insert together so two concurrent
        // invites for the same email/wallet cannot both create a row.
        $member = DB::transaction(function () use ($wallet, $email, $role) {
            // A still-valid unclaimed invite for the same email/wallet blocks a duplicate.
            $existing = $wallet->members()
                ->whereNull('user_id')
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if ($existing && ! $existing->isExpired()) {
                throw ValidationException::withMessages([
                    'email' => 'Já existe um convite pendente para este e-mail.',
                ]);
            }

            // Replace an expired invite rather than piling up rows.
            $existing?->forceDelete();

            return $wallet->members()->create([
                'user_id' => null,
                'email' => $email,
                'role' => $role,
                'invitation_token' => bin2hex(random_bytes(32)),
                'accepted_at' => null,
                'expires_at' => now()->addDays(self::INVITE_TTL_DAYS),
            ]);
        });

        $this->sendInvitationEmail(
            $email, // name unknown — fall back to the email
            $email,
            $inviter->name,
            $wallet->name,
            $role,
            route('register', ['invite' => $member->invitation_token]),
        );

        return $member;
    }

    /**
     * Attach any pending unclaimed invites to a verified user whose email
     * matches. Called after email verification (ClaimWalletInvitations
     * listener on the Verified event) so invites are only granted to the
     * confirmed owner of the address.
     *
     * @return int number of invitations claimed
     */
    public function claimInvitationsForUser(User $user): int
    {
        $email = mb_strtolower($user->email);

        // Lock the claimable invites so a duplicate verification event cannot
        // claim the same rows twice.
        return DB::transaction(function () use ($user, $email): int {
            $invites = WalletMember::query()
                ->claimableForEmail($email)
                ->lockForUpdate()
                ->get();

            // Wallets where this user is already a member, fetched once to
            // avoid an exists() query per invite.
            $existingWalletIds = WalletMember::query()
                ->whereIn('wallet_id', $invites->pluck('wallet_id'))
                ->where('user_id', $user->id)
                ->pluck('wallet_id')
                ->all();

            $claimed = 0;

            foreach ($invites as $invite) {
                // Edge case: invited by email AND already a member via account.
                if (in_array($invite->wallet_id, $existingWalletIds, true)) {
                    $invite->forceDelete();

                    continue;
                }

                $invite->update([
                    'user_id' => $user->id,
                    'invitation_token' => null,
                    'expires_at' => null,
                ]);
                $claimed++;
            }

            return $claimed;
        });
    }

    public function accept(WalletMember $member, User $user): WalletMember
    {
        if ((string) $member->user_id !== (string) $user->id) {
            throw ValidationException::withMessages([
                'member' => 'Este convite não pertence a você.',
            ]);
        }

        if ($member->isAccepted()) {
            return $member;
        }

        DB::transaction(function () use ($member): void {
            $member->update(['accepted_at' => now()]);
            $this->syncWalletSharedFlag($member->wallet_id);
        });

        return $member->refresh();
    }

    public function revoke(WalletMember $member): bool
    {
        // Covers both "invitee declines" and "owner removes member".
        // SoftDeletes keeps an audit trail; the wallet flag is recomputed from
        // the members that remain accepted.
        $walletId = $member->wallet_id;

        return (bool) DB::transaction(function () use ($member, $walletId) {
            $deleted = $member->delete();
            $this->syncWalletSharedFlag($walletId);

            return $deleted;
        });
    }

    public function membersOf(Wallet $wallet): Collection
    {
        return $wallet->members()->with('user')->get();
    }

    public function pendingInvitationsFor(User $user): Collection
    {
        // Eager-load the wallet + owner for display; wallet() ignores the
        // user global scope since WalletMember has no BelongsToUser trait.
        return WalletMember::query()
            ->where('user_id', $user->id)
            ->whereNull('accepted_at')
            ->with(['wallet' => fn ($q) => $q->withoutGlobalScope('user')->with('user')])
            ->latest()
            ->get()
            // Drop invitations whose wallet was deleted in the meantime.
            ->filter(fn (WalletMember $member): bool => $member->wallet !== null)
            ->values();
    }

    /**
     * Recompute the wallet's `is_shared` flag from its accepted memberships.
     *
     * Runs with the user global scope disabled because acceptance/revocation
     * can be triggered by the invitee, who is not the wallet owner.
     */
    private function syncWalletSharedFlag(string $walletId): void
    {
        $wallet = Wallet::withoutGlobalScope('user')->find($walletId);

        if (! $wallet) {
            return;
        }

        $hasAcceptedMembers = $wallet->members()->whereNotNull('accepted_at')->exists();

        if ((bool) $wallet->is_shared !== $hasAcceptedMembers) {
            $wallet->update(['is_shared' => $hasAcceptedMembers]);
        }
    }

    /**
     * Notify the invitee by email. Failures are swallowed so a mail outage
     * never blocks the invitation from being recorded.
     *
     * @param  string  $actionUrl  wallets page (registered) or register link (unregistered)
     */
    private function sendInvitationEmail(
        string $inviteeName,
        string $inviteeEmail,
        string $inviterName,
        string $walletName,
        string $role,
        string $actionUrl,
    ): void {
        $needsRegistration = ! User::where('email', $inviteeEmail)->exists();

        try {
            Mail::to($inviteeEmail)->queue(new WalletInvitationMail(
                inviteeName: $inviteeName,
                inviterName: $inviterName,
                walletName: $walletName,
                roleLabel: self::ROLE_LABELS[$role] ?? $role,
                actionUrl: $actionUrl,
                needsRegistration: $needsRegistration,
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
