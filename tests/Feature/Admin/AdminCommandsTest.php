<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/*
|--------------------------------------------------------------------------
| finestro:make-admin and finestro:admin-mfa-reset
|--------------------------------------------------------------------------
*/

it('promotes an existing verified user to admin and logs it', function (): void {
    $user = User::factory()->create();

    $this->artisan('finestro:make-admin', ['email' => $user->email])
        ->expectsOutputToContain('agora é administrador')
        ->assertExitCode(0);

    expect($user->fresh()->hasRole('admin'))->toBeTrue();

    $log = Activity::query()->where('log_name', 'security')->where('event', 'admin_role_assigned')->first();

    expect($log)->not->toBeNull()
        ->and($log->subject_id)->toBe($user->id)
        ->and($log->properties['via'])->toBe('console')
        ->and($log->properties['role'])->toBe('admin');
});

it('refuses unknown, unverified and suspended users', function (string $state): void {
    $email = match ($state) {
        'unknown' => 'nobody@example.com',
        'unverified' => User::factory()->unverified()->create()->email,
        'suspended' => User::factory()->suspended()->create()->email,
    };

    $this->artisan('finestro:make-admin', ['email' => $email])->assertExitCode(1);

    $user = User::query()->where('email', $email)->first();

    expect($user?->hasRole('admin') ?? false)->toBeFalse();
})->with(['unknown', 'unverified', 'suspended']);

it('refuses a soft-deleted user', function (): void {
    $user = User::factory()->create();
    $user->delete();

    $this->artisan('finestro:make-admin', ['email' => $user->email])->assertExitCode(1);
});

it('resets an admin\'s MFA secret and recovery codes and logs it', function (): void {
    $admin = User::factory()->admin()->create();
    $admin->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
    $admin->saveAppAuthenticationRecoveryCodes(['hashed-code']);

    $this->artisan('finestro:admin-mfa-reset', ['email' => $admin->email, '--force' => true])->assertExitCode(0);

    $admin->refresh();

    expect($admin->getAppAuthenticationSecret())->toBeNull()
        ->and($admin->getAppAuthenticationRecoveryCodes())->toBeNull();

    $log = Activity::query()->where('log_name', 'security')->where('event', 'admin_mfa_reset')->first();

    expect($log)->not->toBeNull()
        ->and($log->properties['via'])->toBe('console');
});

it('keeps the MFA secret when the reset is not confirmed', function (): void {
    $admin = User::factory()->admin()->create();
    $admin->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

    $this->artisan('finestro:admin-mfa-reset', ['email' => $admin->email])
        ->expectsConfirmation("Remover o MFA de {$admin->email}? A configuração será exigida no próximo login em /admin.", 'no')
        ->assertExitCode(1);

    expect($admin->fresh()->getAppAuthenticationSecret())->toBe('JBSWY3DPEHPK3PXP');
});
