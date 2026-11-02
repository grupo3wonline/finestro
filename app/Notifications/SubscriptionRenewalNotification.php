<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Reminder sent once per billing period, a few days before the Professional
 * subscription renews (or ends, when cancellation is already scheduled).
 */
class SubscriptionRenewalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(public readonly Subscription $subscription) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $days = $this->subscription->daysUntilPeriodEnd() ?? 0;
        $when = $days === 0 ? 'hoje' : ($days === 1 ? 'em 1 dia' : "em {$days} dias");
        $date = $this->subscription->current_period_end?->format('d/m/Y') ?? '';

        $message = (new MailMessage)->greeting('Olá, '.$notifiable->name.'!');

        if ($this->subscription->cancel_at_period_end) {
            return $message
                ->subject("Sua assinatura Profissional termina {$when}")
                ->line("Você agendou o cancelamento da assinatura Profissional. O acesso aos recursos Profissionais termina {$when} ({$date}) e a conta volta ao plano Básico.")
                ->line('Quer continuar com carteiras, transações, relatórios e IA ilimitados? Retome a assinatura antes dessa data.')
                ->action('Retomar assinatura', route('subscription.index'));
        }

        return $message
            ->subject("Sua assinatura Profissional renova {$when}")
            ->line("Sua assinatura Profissional será renovada automaticamente {$when} ({$date}), com a cobrança no cartão cadastrado.")
            ->line('Confira seus dados de pagamento para evitar a suspensão dos recursos. Se preferir não renovar, você pode cancelar antes dessa data.')
            ->action('Gerenciar assinatura', route('subscription.index'));
    }
}
