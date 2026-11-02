<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

/*
|--------------------------------------------------------------------------
| Accounts are active only after the e-mail is confirmed
|--------------------------------------------------------------------------
*/

/**
 * @return array<string, string>
 */
function activationSignup(string $username = 'ana_ativa', string $email = 'ana@example.com'): array
{
    return [
        'name' => 'Ana Ativa',
        'username' => $username,
        'email' => $email,
        'password' => 'senha-segura-123',
        'password_confirmation' => 'senha-segura-123',
        'terms' => '1',
    ];
}

function activationLink(User $user, ?string $hash = null): string
{
    return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => $hash ?? sha1($user->email),
    ]);
}

it('creates the account, queues the confirmation e-mail and opens no session', function (): void {
    Notification::fake();

    $this->post('/register', activationSignup())
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');

    $user = User::query()->where('email', 'ana@example.com')->sole();

    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentToTimes($user, QueuedVerifyEmail::class, 1);
    $this->assertGuest('web');
});

it('refuses to sign in an unconfirmed account and offers a new link', function (): void {
    $user = User::factory()->unverified()->create();

    $credentials = ['username' => $user->username, 'email' => $user->email, 'password' => 'password'];

    $this->from('/login')->post('/login', $credentials)
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['login' => User::UNVERIFIED_MESSAGE]);
    $this->assertGuest('web');

    $this->from('/login')->followingRedirects()->post('/login', $credentials)
        ->assertSee(User::UNVERIFIED_MESSAGE)
        ->assertSee(route('verification.resend.create'), false);
});

it('does not reveal that an account is unconfirmed when the password is wrong', function (): void {
    $user = User::factory()->unverified()->create();

    $this->from('/login')->post('/login', ['username' => $user->username, 'email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors(['login' => trans('auth.failed')]);
});

it('lets a suspended and unconfirmed account see the suspension message first', function (): void {
    $user = User::factory()->unverified()->suspended()->create();

    $this->from('/login')->post('/login', ['username' => $user->username, 'email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['login' => User::SUSPENDED_MESSAGE]);
});

it('confirms the e-mail from the signed link without a session', function (): void {
    Event::fake([Verified::class]);
    $user = User::factory()->unverified()->create();

    $this->get(activationLink($user))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class, fn (Verified $event): bool => $event->user->is($user));
    $this->assertGuest('web');
});

it('signs in normally after the confirmation', function (): void {
    $user = User::factory()->unverified()->create();

    $this->get(activationLink($user));

    $this->post('/login', ['username' => $user->username, 'email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user, 'web');
});

it('sends someone already signed in as that account to the dashboard', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(activationLink($user))
        ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects a link with a wrong hash, an unknown user or a bad signature', function (): void {
    $user = User::factory()->unverified()->create();

    $this->get(activationLink($user, 'not-the-hash'))->assertForbidden();
    $this->get(URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => '00000000-0000-0000-0000-000000000000', 'hash' => sha1($user->email)]))
        ->assertForbidden();
    $this->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))->assertForbidden();
    $this->get(URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)]))
        ->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('keeps the link valid for 24 hours by default', function (): void {
    expect(config('auth.verification.expire'))->toBe(1440);
});

it('sends a new link from the public form', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->get('/reenviar-confirmacao')->assertOk()->assertSee('Reenviar e-mail de confirmação');

    $this->post('/reenviar-confirmacao', ['email' => $user->email])->assertSessionHas('status');

    Notification::assertSentTo($user, QueuedVerifyEmail::class);
});

it('answers the same for unknown, confirmed and suspended addresses and sends nothing', function (): void {
    Notification::fake();
    $confirmed = User::factory()->create();
    $suspended = User::factory()->unverified()->suspended()->create();

    $statuses = collect(['nobody@example.com', $confirmed->email, $suspended->email])
        ->map(fn (string $email) => $this->post('/reenviar-confirmacao', ['email' => $email])->getSession()->get('status'));

    expect($statuses->unique())->toHaveCount(1)->and($statuses->first())->not->toBeNull();
    Notification::assertNothingSent();
});

it('limits the resend form to 3 e-mails per address per hour', function (): void {
    Notification::fake();
    RateLimiter::clear('resend-verification:'.sha1('limite@example.com'));
    $user = User::factory()->unverified()->create(['email' => 'limite@example.com']);

    foreach (range(1, 5) as $attempt) {
        $this->post('/reenviar-confirmacao', ['email' => 'LIMITE@example.com'])->assertSessionHas('status');
    }

    Notification::assertSentToTimes($user, QueuedVerifyEmail::class, 3);
});

it('keeps the legacy resend for a session that already exists', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->post(route('verification.send'))->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($user, QueuedVerifyEmail::class);
});

it('writes the activation and reset e-mails in Brazilian Portuguese', function (): void {
    $user = User::factory()->unverified()->create();

    $activation = (string) (new QueuedVerifyEmail)->toMail($user)->render();
    $reset = (string) (new QueuedResetPassword('token'))->toMail($user)->render();

    expect($activation)
        ->toContain('Confirmar e-mail')
        ->toContain('Atenciosamente')
        ->toContain('24 hora')
        ->not->toContain('Regards')
        ->not->toContain('All rights reserved');

    expect($reset)
        ->toContain('Redefinir senha')
        ->toContain('expira em 60 minutos')
        ->not->toContain('Reset Password');
});
