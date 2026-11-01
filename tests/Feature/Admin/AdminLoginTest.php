<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

/*
|--------------------------------------------------------------------------
| Filament panel login: access rule, TOTP challenge, recovery, throttling
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function adminLogin(User $user, string $password = 'password'): Testable
{
    return Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', $password)
        ->call('authenticate');
}

it('rejects a plain user', function (): void {
    $user = User::factory()->create();

    adminLogin($user)->assertHasErrors(['data.email']);

    $this->assertGuest('admin');
});

it('rejects a suspended admin', function (): void {
    adminLogin(mfaAdmin(['suspended_at' => now()]))->assertHasErrors(['data.email']);

    $this->assertGuest('admin');
});

it('rejects a wrong password', function (): void {
    adminLogin(mfaAdmin(), 'wrong-password')->assertHasErrors(['data.email']);

    $this->assertGuest('admin');
});

it('challenges an MFA admin and signs in with a valid TOTP code', function (): void {
    $admin = mfaAdmin();

    $component = adminLogin($admin)->assertHasNoErrors();

    expect($component->get('userUndertakingMultiFactorAuthentication'))->not->toBeEmpty();
    $this->assertGuest('admin');

    $code = app(Google2FA::class)->getCurrentOtp($admin->getAppAuthenticationSecret());

    $component->set('data.multiFactor.app.code', $code)
        ->call('authenticate')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($admin, 'admin');
    $this->assertGuest('web');
});

it('rejects a wrong TOTP code', function (): void {
    $admin = mfaAdmin();

    adminLogin($admin)
        ->set('data.multiFactor.app.code', '000000')
        ->call('authenticate')
        ->assertHasErrors(['data.multiFactor.app.code']);

    $this->assertGuest('admin');
});

it('accepts a recovery code only once', function (): void {
    $admin = mfaAdmin();
    app(AppAuthentication::class)->saveRecoveryCodes($admin, ['recovery-code-1', 'recovery-code-2']);

    adminLogin($admin)
        ->set('data.multiFactor.app.useRecoveryCode', true)
        ->set('data.multiFactor.app.recoveryCode', 'recovery-code-1')
        ->call('authenticate')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($admin, 'admin');
    expect($admin->fresh()->getAppAuthenticationRecoveryCodes())->toHaveCount(1);

    Filament::auth()->logout();

    adminLogin($admin)
        ->set('data.multiFactor.app.useRecoveryCode', true)
        ->set('data.multiFactor.app.recoveryCode', 'recovery-code-1')
        ->call('authenticate')
        ->assertHasErrors(['data.multiFactor.app.recoveryCode']);

    $this->assertGuest('admin');
});

it('rate limits the 6th login attempt within a minute', function (): void {
    $admin = mfaAdmin();

    $component = Livewire::test(Login::class)
        ->set('data.email', $admin->email)
        ->set('data.password', 'wrong-password');

    foreach (range(1, 5) as $attempt) {
        $component->call('authenticate')->assertHasErrors(['data.email']);
    }

    // Even the right password is refused once the limit is hit.
    $component->set('data.password', 'password')
        ->call('authenticate')
        ->assertNotified()
        ->assertSet('userUndertakingMultiFactorAuthentication', null);

    $this->assertGuest('admin');
});
