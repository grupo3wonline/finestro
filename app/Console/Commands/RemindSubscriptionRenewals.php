<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Interfaces\Services\AccountReminderServiceInterface;
use Illuminate\Console\Command;

class RemindSubscriptionRenewals extends Command
{
    protected $signature = 'billing:remind-renewals';

    protected $description = 'Envia o lembrete por e-mail a quem tem a assinatura Profissional renovando (ou terminando) em poucos dias';

    public function handle(AccountReminderServiceInterface $reminders): int
    {
        $sent = $reminders->remindSubscriptionRenewals();

        $this->info("Lembretes de renovação enviados para a fila: {$sent}.");

        return self::SUCCESS;
    }
}
