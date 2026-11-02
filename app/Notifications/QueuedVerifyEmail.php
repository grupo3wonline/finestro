<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Breeze e-mail verification, sent through the queue so registration does
 * not wait on SMTP. The signed link uses APP_URL, so it must be set correctly
 * for the worker process.
 */
class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    /**
     * Account activation e-mail (pt-BR strings live in lang/pt_BR.json).
     *
     * @param  string  $url
     */
    protected function buildMailMessage($url): MailMessage
    {
        $hours = (int) round((int) config('auth.verification.expire', 60) / 60);

        return parent::buildMailMessage($url)
            ->line('Sua conta só é ativada depois dessa confirmação. O link é válido por '.max(1, $hours).' hora(s); depois disso, peça um novo link na tela de login.');
    }
}
