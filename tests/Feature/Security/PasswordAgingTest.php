<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\PasswordExpiringNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Notification;

/*
|--------------------------------------------------------------------------
| Passwords expire every 90 days; warning (banner + e-mail) in the last 7
|--------------------------------------------------------------------------
*/

function userWithPasswordAge(int $days, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->forceFill(['password_changed_at' => now()->subDays($days)])->save();

    return $user->fresh();
}

it('defaults to a 90-day lifetime with a 7-day warning', function (): void {
    expect(config('security.password_max_age_days'))->toBe(90)
        ->and(config('security.password_warning_days'))->toBe(7);
});

it('starts the clock when the account is created', function (): void {
    $user = User::factory()->create();

    expect($user->password_changed_at)->not->toBeNull()
        ->and($user->passwordDaysRemaining())->toBe(90)
        ->and($user->isPasswordExpired())->toBeFalse()
        ->and($user->isPasswordExpiringSoon())->toBeFalse();
});

it('restarts the clock only when the password changes', function (): void {
    $user = userWithPasswordAge(50);

    $user->update(['name' => 'Outro Nome']);
    expect($user->fresh()->password_changed_at->diffInDays(now()))->toBeGreaterThan(49);

    $user->update(['password' => 'brand-new-pass-1']);
    expect($user->fresh()->password_changed_at->diffInMinutes(now()))->toBeLessThan(2);
});

it('restarts the clock through a reset link', function (): void {
    $user = userWithPasswordAge(80);
    $token = app('auth.password.broker')->createToken($user);

    $this->post(route('password.store'), [
        'token' => $token, 'email' => $user->email,
        'password' => 'reset-pass-123', 'password_confirmation' => 'reset-pass-123',
    ])->assertSessionHasNoErrors();

    expect($user->fresh()->isPasswordExpiringSoon())->toBeFalse()
        ->and($user->fresh()->passwordDaysRemaining())->toBe(90);
});

it('calculates the days left and the warning window', function (int $age, int $left, bool $soon, bool $expired): void {
    $user = userWithPasswordAge($age);

    expect($user->passwordDaysRemaining())->toBe($left)
        ->and($user->isPasswordExpiringSoon())->toBe($soon)
        ->and($user->isPasswordExpired())->toBe($expired);
})->with([
    'fresh' => [0, 90, false, false],
    'before the window' => [82, 8, false, false],
    'first day of the window' => [83, 7, true, false],
    'last day' => [89, 1, true, false],
    'expired' => [91, 0, false, true],
]);

it('blocks every app page with an expired password and lets only the change screen through', function (): void {
    $user = userWithPasswordAge(91);

    foreach (['/dashboard', '/wallets', '/transactions', '/goals', '/configuracoes', '/aceitar-termos'] as $url) {
        $this->actingAs($user)->get($url)->assertRedirect(route('password.expired'));
    }

    $this->actingAs($user)->get(route('password.expired'))->assertOk()->assertSee('Sua senha expirou');
    $this->actingAs($user)->post(route('logout'))->assertRedirect();
});

it('does not block a password that is merely close to expiring', function (): void {
    $this->actingAs(userWithPasswordAge(89))->get('/dashboard')->assertOk();
});

it('sets a new password from the forced screen and releases the account', function (): void {
    $user = userWithPasswordAge(120);

    $this->actingAs($user)->put(route('password.expired.update'), [
        'current_password' => 'password',
        'password' => 'novasenha-456',
        'password_confirmation' => 'novasenha-456',
    ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));

    $fresh = $user->fresh();
    expect($fresh->isPasswordExpired())->toBeFalse()
        ->and(Hash::check('novasenha-456', $fresh->password))->toBeTrue();

    $this->actingAs($fresh)->get('/dashboard')->assertOk();
});

it('rejects the same password, a wrong current password and a bad confirmation on the forced screen', function (): void {
    $user = userWithPasswordAge(120);

    $this->actingAs($user)->put(route('password.expired.update'), [
        'current_password' => 'password', 'password' => 'password', 'password_confirmation' => 'password',
    ])->assertSessionHasErrors('password');

    $this->actingAs($user)->put(route('password.expired.update'), [
        'current_password' => 'wrong', 'password' => 'novasenha-456', 'password_confirmation' => 'novasenha-456',
    ])->assertSessionHasErrors('current_password');

    $this->actingAs($user)->put(route('password.expired.update'), [
        'current_password' => 'password', 'password' => 'novasenha-456', 'password_confirmation' => 'different-1',
    ])->assertSessionHasErrors('password');

    expect($user->fresh()->isPasswordExpired())->toBeTrue();
});

it('sends an unexpired password away from the forced screen', function (): void {
    $this->actingAs(userWithPasswordAge(10))->get(route('password.expired'))->assertRedirect(route('dashboard'));
});

it('lets an expired account sign in and then forces the change', function (): void {
    $user = userWithPasswordAge(100);

    $this->post('/login', ['username' => $user->username, 'email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->get('/dashboard')->assertRedirect(route('password.expired'));
});

it('requires a different password on the settings and profile forms', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('settings.password'), [
        'current_password' => 'password', 'password' => 'password', 'password_confirmation' => 'password',
    ])->assertSessionHasErrors('password');

    $this->actingAs($user)->put(route('password.update'), [
        'current_password' => 'password', 'password' => 'password', 'password_confirmation' => 'password',
    ])->assertSessionHasErrorsIn('updatePassword', 'password');
});

it('shows the warning banner only in the last 7 days', function (): void {
    $this->actingAs(userWithPasswordAge(80))->get('/dashboard')->assertOk()->assertDontSee('Sua senha expira');

    $this->actingAs(userWithPasswordAge(84))->get('/dashboard')->assertOk()
        ->assertSee('Sua senha expira em 6 dias')
        ->assertSee('Trocar senha');

    $this->actingAs(userWithPasswordAge(89))->get('/dashboard')->assertSee('Sua senha expira em 1 dia');
});

it('shows the expiry date in the settings page', function (): void {
    $user = userWithPasswordAge(30);

    $this->actingAs($user)->get(route('settings.index'))->assertOk()
        ->assertSee('id="alterar-senha"', false)
        ->assertSee($user->passwordExpiresAt()->format('d/m/Y'))
        ->assertSee('60 dias restantes');
});

it('reminds each password once, only inside the warning window', function (): void {
    Notification::fake();

    $inside = userWithPasswordAge(84);
    $early = userWithPasswordAge(60);
    $expired = userWithPasswordAge(95);
    $unverified = userWithPasswordAge(84, ['email_verified_at' => null]);
    $suspended = userWithPasswordAge(84, ['suspended_at' => now()]);

    $this->artisan('security:remind-password-expiry')->assertSuccessful();

    Notification::assertSentTo($inside, PasswordExpiringNotification::class);
    Notification::assertNotSentTo([$early, $expired, $unverified, $suspended], PasswordExpiringNotification::class);
    expect($inside->fresh()->password_expiry_reminded_at)->not->toBeNull();

    Notification::fake();
    $this->artisan('security:remind-password-expiry')->assertSuccessful();
    Notification::assertNothingSent();
});

it('reminds again after the user changes the password and it ages into the window', function (): void {
    Notification::fake();
    $user = userWithPasswordAge(84);
    $this->artisan('security:remind-password-expiry');
    Notification::assertSentToTimes($user, PasswordExpiringNotification::class, 1);

    $user->update(['password' => 'another-pass-123']);
    // The new password is newer than the reminder sent for the previous one.
    $user->forceFill(['password_changed_at' => now()->subDays(85), 'password_expiry_reminded_at' => now()->subDays(86)])->save();

    $this->artisan('security:remind-password-expiry');
    Notification::assertSentToTimes($user, PasswordExpiringNotification::class, 2);
});

it('writes the reminder e-mail with the expiry date and a link to the password form', function (): void {
    $user = userWithPasswordAge(86);
    $mail = (new PasswordExpiringNotification)->toMail($user);
    $html = (string) $mail->render();

    expect($mail->subject)->toBe('Sua senha do Finestro expira em 4 dias')
        ->and($html)->toContain($user->passwordExpiresAt()->format('d/m/Y'))
        ->and($html)->toContain('#alterar-senha');
});

it('schedules both reminder commands daily at 09:00', function (): void {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event): bool => str_contains((string) $event->command, 'security:remind-password-expiry')
            || str_contains((string) $event->command, 'billing:remind-renewals'));

    expect($events)->toHaveCount(2)
        ->and($events->every(fn ($event): bool => $event->expression === '0 9 * * *'))->toBeTrue();
});
