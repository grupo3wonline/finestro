<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Reminder sent once per password, inside the last days before it expires.
 */
class PasswordExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $days = $notifiable->passwordDaysRemaining();
        $when = $days <= 1 ? ($days === 0 ? 'hoje' : 'em 1 dia') : "em {$days} dias";

        return (new MailMessage)
            ->subject($days === 0 ? 'Sua senha do Finestro expira hoje' : "Sua senha do Finestro expira {$when}")
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Por segurança, a senha do Finestro precisa ser trocada a cada '.config('security.password_max_age_days').' dias.')
            ->line('A sua senha expira '.$when.' ('.$notifiable->passwordExpiresAt()->format('d/m/Y').'). Depois dessa data você só conseguirá acessar a tela de troca de senha.')
            ->action('Trocar minha senha agora', route('settings.index').'#alterar-senha')
            ->line('Se você já trocou a senha hoje, pode ignorar este aviso.');
    }
}
