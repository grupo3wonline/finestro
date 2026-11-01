<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Interfaces\Services\SecurityActivityLoggerInterface;
use App\Models\User;
use Illuminate\Console\Command;

class ResetAdminMfa extends Command
{
    protected $signature = 'finestro:admin-mfa-reset
        {email : E-mail of the user whose MFA must be reset}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Clear the app (TOTP) MFA secret and recovery codes of a user; MFA set-up is required on next /admin login';

    public function handle(SecurityActivityLoggerInterface $logger): int
    {
        $email = (string) $this->argument('email');

        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            $this->error("Usuário não encontrado: {$email}");

            return Command::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Remover o MFA de {$email}? A configuração será exigida no próximo login em /admin.")) {
            $this->warn('Operação cancelada.');

            return Command::FAILURE;
        }

        $user->forceFill([
            'app_authentication_secret' => null,
            'app_authentication_recovery_codes' => null,
        ])->save();

        $logger->log('admin_mfa_reset', $user, ['via' => 'console']);

        $this->info("MFA de {$email} removido. A configuração será exigida no próximo login.");

        return Command::SUCCESS;
    }
}
