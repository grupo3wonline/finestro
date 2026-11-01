<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to a user who was invited to share a wallet.
 *
 * Carries primitives only (no Eloquent models) so it serializes cleanly when
 * queued and stays decoupled from the sharing domain.
 */
class WalletInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(
        public string $inviteeName,
        public string $inviterName,
        public string $walletName,
        public string $roleLabel,
        public string $actionUrl,
        public bool $needsRegistration = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->inviterName.' convidou você para a carteira '.$this->walletName,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.wallet-invitation');
    }
}
