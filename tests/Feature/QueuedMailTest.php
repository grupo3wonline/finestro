<?php

declare(strict_types=1);

use App\Mail\FinancialReportMail;
use App\Mail\WalletInvitationMail;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

function queuedMailProUser(): User
{
    $user = User::factory()->create();
    Subscription::factory()->for($user)->create();

    return $user->fresh();
}

/**
 * True when $value (recursively) contains an Eloquent model or any collection.
 */
function containsModelOrCollection(mixed $value): bool
{
    if ($value instanceof Model || $value instanceof Collection) {
        return true;
    }

    if (is_array($value)) {
        foreach ($value as $item) {
            if (containsModelOrCollection($item)) {
                return true;
            }
        }
    }

    return false;
}

it('queues the report e-mail to the own address with a scalar payload', function (): void {
    Mail::fake();
    $user = queuedMailProUser();

    $this->actingAs($user)->post(route('reports.email'))->assertSessionHas('success');

    Mail::assertQueued(FinancialReportMail::class, function (FinancialReportMail $mail) use ($user): bool {
        return $mail->hasTo($user->email)
            && ! containsModelOrCollection($mail->report)
            && array_key_exists('transactionCount', $mail->report);
    });
    Mail::assertNotSent(FinancialReportMail::class);
});

it('queues the wallet invitation e-mail', function (): void {
    Mail::fake();
    Notification::fake();
    $owner = queuedMailProUser();
    $wallet = Wallet::factory()->for($owner)->create(['ownership' => 'business']);

    $this->actingAs($owner)->post(route('wallets.share', $wallet), [
        'email' => 'convidado@example.com',
        'role' => 'viewer',
    ])->assertSessionHasNoErrors();

    Mail::assertQueued(WalletInvitationMail::class, fn (WalletInvitationMail $mail): bool => $mail->hasTo('convidado@example.com'));
    Mail::assertNotSent(WalletInvitationMail::class);
});

it('marks the mail and notification classes as queued with retries', function (string $class): void {
    $reflection = new ReflectionClass($class);
    $instance = $reflection->newInstanceWithoutConstructor();

    expect($instance)->toBeInstanceOf(ShouldQueue::class)
        ->and($reflection->getProperty('tries')->getDefaultValue())->toBe(3)
        ->and($reflection->getProperty('backoff')->getDefaultValue())->toBe([60, 300]);
})->with([
    FinancialReportMail::class,
    WalletInvitationMail::class,
    QueuedVerifyEmail::class,
    QueuedResetPassword::class,
]);

it('sends the queued reset password notification on forgot-password', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, QueuedResetPassword::class);
});

it('sends the queued verification notification on registration', function (): void {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Fila Teste',
        'username' => 'filateste',
        'email' => 'fila@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => '1',
    ]);

    $user = User::where('email', 'fila@example.com')->firstOrFail();

    Notification::assertSentTo($user, QueuedVerifyEmail::class);
});
