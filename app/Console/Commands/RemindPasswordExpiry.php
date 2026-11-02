<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Interfaces\Services\AccountReminderServiceInterface;
use Illuminate\Console\Command;

class RemindPasswordExpiry extends Command
{
    protected $signature = 'security:remind-password-expiry';

    protected $description = 'Envia o lembrete por e-mail a quem está a poucos dias de ter a senha expirada';

    public function handle(AccountReminderServiceInterface $reminders): int
    {
        $sent = $reminders->remindPasswordExpiry();

        $this->info("Lembretes de expiração de senha enviados para a fila: {$sent}.");

        return self::SUCCESS;
    }
}
