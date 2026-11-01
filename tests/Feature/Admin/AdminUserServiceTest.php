<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Exceptions\AccountDeletionException;
use App\Exceptions\AdminActionException;
use App\Exceptions\StripeResourceMissingException;
use App\Interfaces\Services\AdminUserServiceInterface;
use App\Interfaces\Services\StripeGatewayInterface;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Tests\Fakes\FakeStripeGateway;

/*
|--------------------------------------------------------------------------
| AdminUserService: actions, guards and security log causer
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    $this->fake = new FakeStripeGateway;
    $this->app->instance(StripeGatewayInterface::class, $this->fake);

    $this->service = app(AdminUserServiceInterface::class);
    $this->admin = User::factory()->admin()->create();
});

function adminSecurityEntry(string $event): ?Activity
{
    return Activity::query()->where('log_name', 'security')->where('event', $event)->latest('id')->first();
}

it('promotes a user and logs the admin as causer', function (): void {
    $target = User::factory()->create();

    $this->service->promote($this->admin, $target);

    $entry = adminSecurityEntry('admin_role_assigned');

    expect($target->fresh()->hasRole('admin'))->toBeTrue()
        ->and($entry->causer_id)->toBe($this->admin->id)
        ->and($entry->subject_id)->toBe($target->id)
        ->and($entry->getProperty('role'))->toBe('admin')
        ->and($entry->getProperty('via'))->toBe('admin');
});

it('refuses to promote an existing admin', function (): void {
    $other = User::factory()->admin()->create();

    expect(fn () => $this->service->promote($this->admin, $other))
        ->toThrow(AdminActionException::class, 'Este usuário já é administrador.');
});

it('refuses to promote an unverified or suspended user, like finestro:make-admin', function (string $state, string $message): void {
    $target = User::factory()->{$state}()->create();

    expect(fn () => $this->service->promote($this->admin, $target))
        ->toThrow(AdminActionException::class, $message)
        ->and($target->fresh()->hasRole('admin'))->toBeFalse()
        ->and(adminSecurityEntry('admin_role_assigned'))->toBeNull();
})->with([
    'unverified' => ['unverified', AdminUserServiceInterface::PROMOTE_UNVERIFIED_MESSAGE],
    'suspended' => ['suspended', AdminUserServiceInterface::PROMOTE_SUSPENDED_MESSAGE],
]);

it('demotes another admin when an active admin remains', function (): void {
    $other = User::factory()->admin()->create();

    $this->service->demote($this->admin, $other);

    expect($other->fresh()->hasRole('admin'))->toBeFalse()
        ->and(adminSecurityEntry('admin_role_removed')->causer_id)->toBe($this->admin->id);
});

it('refuses self-demotion', function (): void {
    User::factory()->admin()->create();

    expect(fn () => $this->service->demote($this->admin, $this->admin))
        ->toThrow(AdminActionException::class, 'Você não pode remover o seu próprio acesso de administrador.');

    expect($this->admin->fresh()->hasRole('admin'))->toBeTrue()
        ->and(adminSecurityEntry('admin_role_removed'))->toBeNull();
});

it('refuses to demote the last active admin', function (): void {
    // The acting admin is suspended, so the target is the only active admin.
    $this->admin->forceFill(['suspended_at' => now()])->save();
    $other = User::factory()->admin()->create();

    expect(fn () => $this->service->demote($this->admin, $other))
        ->toThrow(AdminActionException::class, 'É necessário manter pelo menos um administrador ativo.');

    expect($other->fresh()->hasRole('admin'))->toBeTrue();
});

it('suspends a user, revokes its API tokens and logs the admin', function (): void {
    $target = User::factory()->create();
    $target->createToken('integracao');
    $other = User::factory()->create();
    $other->createToken('outro');

    $this->service->suspend($this->admin, $target);

    expect($target->fresh()->isSuspended())->toBeTrue()
        ->and(adminSecurityEntry('admin_user_suspended')->causer_id)->toBe($this->admin->id);
    $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $target->id]);
    $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $other->id]);
});

it('refuses to suspend yourself', function (): void {
    expect(fn () => $this->service->suspend($this->admin, $this->admin))
        ->toThrow(AdminActionException::class, 'Você não pode suspender a sua própria conta.');

    expect($this->admin->fresh()->isSuspended())->toBeFalse();
});

it('refuses to suspend the last active admin', function (): void {
    $this->admin->forceFill(['suspended_at' => now()])->save();
    $other = User::factory()->admin()->create();

    expect(fn () => $this->service->suspend($this->admin, $other))
        ->toThrow(AdminActionException::class, 'É necessário manter pelo menos um administrador ativo.');

    expect($other->fresh()->isSuspended())->toBeFalse();
});

it('unsuspends a suspended user', function (): void {
    $target = User::factory()->suspended()->create();

    $this->service->unsuspend($this->admin, $target);

    expect($target->fresh()->isSuspended())->toBeFalse()
        ->and(adminSecurityEntry('admin_user_unsuspended')->causer_id)->toBe($this->admin->id);
});

it('queues a new verification e-mail for an unverified user', function (): void {
    Notification::fake();
    $target = User::factory()->unverified()->create();

    $this->service->resendVerification($this->admin, $target);

    Notification::assertSentTo($target, QueuedVerifyEmail::class);
    expect(adminSecurityEntry('admin_verification_resent')->causer_id)->toBe($this->admin->id);
});

it('refuses to resend verification to a verified e-mail', function (): void {
    Notification::fake();
    $target = User::factory()->create();

    expect(fn () => $this->service->resendVerification($this->admin, $target))
        ->toThrow(AdminActionException::class, 'Este e-mail já foi verificado.');

    Notification::assertNothingSent();
});

it('deletes an account, cancelling Stripe first, with the admin as causer', function (): void {
    $target = User::factory()->create();
    Subscription::factory()->for($target)->create(['stripe_subscription_id' => 'sub_target']);

    $this->service->delete($this->admin, $target->fresh());

    $this->assertSoftDeleted($target);
    expect($this->fake->calls['cancelImmediately'][0][0])->toBe('sub_target')
        ->and(adminSecurityEntry('account_deleted')->causer_id)->toBe($this->admin->id)
        ->and(adminSecurityEntry('admin_user_deleted')->causer_id)->toBe($this->admin->id);
});

it('keeps the account and rethrows when the Stripe cancellation fails', function (): void {
    $target = User::factory()->create();
    Subscription::factory()->for($target)->create(['stripe_subscription_id' => 'sub_target']);
    $this->fake->cancelException = new RuntimeException('stripe down');

    expect(fn () => $this->service->delete($this->admin, $target->fresh()))
        ->toThrow(AccountDeletionException::class, 'não foi excluída');

    $this->assertNotSoftDeleted($target);
    expect(adminSecurityEntry('admin_user_deleted'))->toBeNull();
});

it('deletes the account and marks the subscription canceled when Stripe no longer has it', function (): void {
    $target = User::factory()->create();
    $subscription = Subscription::factory()->for($target)->create(['stripe_subscription_id' => 'sub_gone']);
    $this->fake->cancelException = new StripeResourceMissingException('gone');

    $this->service->delete($this->admin, $target->fresh());

    $this->assertSoftDeleted($target);
    expect(Subscription::withTrashed()->find($subscription->id)->status)->toBe(SubscriptionStatus::CANCELED);
});

it('refuses to delete yourself or the last active admin', function (): void {
    expect(fn () => $this->service->delete($this->admin, $this->admin))
        ->toThrow(AdminActionException::class, 'Você não pode excluir a sua própria conta pelo painel.');

    $this->admin->forceFill(['suspended_at' => now()])->save();
    $other = User::factory()->admin()->create();

    expect(fn () => $this->service->delete($this->admin, $other))
        ->toThrow(AdminActionException::class, 'É necessário manter pelo menos um administrador ativo.');

    $this->assertNotSoftDeleted($this->admin);
    $this->assertNotSoftDeleted($other);
});
