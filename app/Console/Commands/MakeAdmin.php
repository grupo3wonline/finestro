<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Interfaces\Services\AdminUserServiceInterface;
use App\Interfaces\Services\SecurityActivityLoggerInterface;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'finestro:make-admin {email : E-mail of an existing, verified user}';

    protected $description = 'Promote an existing verified user to administrator (Filament /admin panel)';

    public function handle(SecurityActivityLoggerInterface $logger): int
    {
        $email = (string) $this->argument('email');

        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            $this->error("Usuário não encontrado: {$email}");

            return Command::FAILURE;
        }

        if (! $user->hasVerifiedEmail()) {
            $this->error(AdminUserServiceInterface::PROMOTE_UNVERIFIED_MESSAGE);

            return Command::FAILURE;
        }

        if ($user->isSuspended()) {
            $this->error(AdminUserServiceInterface::PROMOTE_SUSPENDED_MESSAGE);

            return Command::FAILURE;
        }

        if ($user->hasRole(RolesAndPermissionsSeeder::ROLE_ADMIN)) {
            $this->info("{$email} já é administrador.");

            return Command::SUCCESS;
        }

        $user->assignRole(RolesAndPermissionsSeeder::ROLE_ADMIN);

        $logger->log('admin_role_assigned', $user, ['role' => RolesAndPermissionsSeeder::ROLE_ADMIN, 'via' => 'console']);

        $this->info("{$email} agora é administrador.");
        $this->newLine();
        $this->line('Próximos passos:');
        $this->line('  1. Acesse '.url('/admin/login').' com e-mail e senha.');
        $this->line('  2. Configure o aplicativo autenticador (MFA) e guarde os códigos de recuperação.');

        return Command::SUCCESS;
    }
}
