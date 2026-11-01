<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Exceptions\StripeResourceMissingException;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Interfaces\Repositories\AdminUserRepositoryInterface;
use App\Interfaces\Services\AdminUserServiceInterface;
use App\Interfaces\Services\StripeGatewayInterface;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\QueuedVerifyEmail;
use App\Repositories\AdminUserRepository;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\Fakes\FakeStripeGateway;

/*
|--------------------------------------------------------------------------
| User actions in the panel (table + view page), guards and causer
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->fake = new FakeStripeGateway;
    $this->app->instance(StripeGatewayInterface::class, $this->fake);

    $this->admin = mfaAdmin();
    $this->actingAs($this->admin, 'admin');
});

function panelActionEntry(string $event): ?Activity
{
    return Activity::query()->where('log_name', 'security')->where('event', $event)->latest('id')->first();
}

it('promotes a user from the table', function (): void {
    $target = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('promote')->table($target))
        ->assertNotified('Usuário promovido a administrador.');

    expect($target->fresh()->hasRole('admin'))->toBeTrue()
        ->and(panelActionEntry('admin_role_assigned')->causer_id)->toBe($this->admin->id)
        ->and(panelActionEntry('admin_role_assigned')->subject_id)->toBe($target->id);
});

it('shows a danger notification and does not promote an unverified or suspended user', function (string $state, string $message): void {
    $target = User::factory()->{$state}()->create();

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('promote')->table($target))
        ->assertNotified($message);

    expect($target->fresh()->hasRole('admin'))->toBeFalse()
        ->and(panelActionEntry('admin_role_assigned'))->toBeNull();
})->with([
    'unverified' => ['unverified', AdminUserServiceInterface::PROMOTE_UNVERIFIED_MESSAGE],
    'suspended' => ['suspended', AdminUserServiceInterface::PROMOTE_SUSPENDED_MESSAGE],
]);

it('demotes another admin from the view page', function (): void {
    $other = User::factory()->admin()->create();

    Livewire::test(ViewUser::class, ['record' => $other->getRouteKey()])
        ->callAction('demote')
        ->assertNotified('Acesso de administrador removido.');

    expect($other->fresh()->hasRole('admin'))->toBeFalse()
        ->and(panelActionEntry('admin_role_removed')->causer_id)->toBe($this->admin->id);
});

it('shows a danger notification and changes nothing on self-demotion', function (): void {
    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('demote')->table($this->admin))
        ->assertNotified('Você não pode remover o seu próprio acesso de administrador.');

    expect($this->admin->fresh()->hasRole('admin'))->toBeTrue()
        ->and(panelActionEntry('admin_role_removed'))->toBeNull();
});

it('shows a danger notification and changes nothing when the last active admin would be removed', function (): void {
    // The acting admin always counts as active in the panel; simulate a
    // concurrent change that left the target as the last active admin.
    $repository = Mockery::mock(AdminUserRepository::class)->makePartial();
    $repository->shouldReceive('countActiveAdmins')->andReturn(0);
    $this->app->instance(AdminUserRepositoryInterface::class, $repository);

    $other = User::factory()->admin()->create();

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('demote')->table($other))
        ->assertNotified('É necessário manter pelo menos um administrador ativo.')
        ->callAction(TestAction::make('suspend')->table($other))
        ->assertNotified('É necessário manter pelo menos um administrador ativo.');

    expect($other->fresh()->hasRole('admin'))->toBeTrue()
        ->and($other->fresh()->isSuspended())->toBeFalse();
});

it('suspends a user and revokes its tokens', function (): void {
    $target = User::factory()->create();
    $target->createToken('integracao');

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('suspend')->table($target))
        ->assertNotified('Conta suspensa.');

    expect($target->fresh()->isSuspended())->toBeTrue()
        ->and(panelActionEntry('admin_user_suspended')->causer_id)->toBe($this->admin->id);
    $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $target->id]);
});

it('refuses to suspend yourself', function (): void {
    Livewire::test(ViewUser::class, ['record' => $this->admin->getRouteKey()])
        ->callAction('suspend')
        ->assertNotified('Você não pode suspender a sua própria conta.');

    expect($this->admin->fresh()->isSuspended())->toBeFalse();
});

it('reactivates a suspended user', function (): void {
    $target = User::factory()->suspended()->create();

    Livewire::test(ListUsers::class)
        ->assertActionHidden(TestAction::make('suspend')->table($target))
        ->callAction(TestAction::make('unsuspend')->table($target))
        ->assertNotified('Conta reativada.');

    expect($target->fresh()->isSuspended())->toBeFalse()
        ->and(panelActionEntry('admin_user_unsuspended')->causer_id)->toBe($this->admin->id);
});

it('resends the verification e-mail only for unverified users', function (): void {
    NotificationFacade::fake();
    $unverified = User::factory()->unverified()->create();
    $verified = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->assertActionHidden(TestAction::make('resendVerification')->table($verified))
        ->callAction(TestAction::make('resendVerification')->table($unverified))
        ->assertNotified('E-mail de verificação reenviado.');

    NotificationFacade::assertSentTo($unverified, QueuedVerifyEmail::class);
    expect(panelActionEntry('admin_verification_resent')->causer_id)->toBe($this->admin->id);
});

it('deletes an account after cancelling Stripe', function (): void {
    $target = User::factory()->create();
    Subscription::factory()->for($target)->create(['stripe_subscription_id' => 'sub_target']);

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('deleteAccount')->table($target->fresh()))
        ->assertNotified('Conta excluída.');

    $this->assertSoftDeleted($target);
    expect($this->fake->calls['cancelImmediately'][0][0])->toBe('sub_target')
        ->and(panelActionEntry('admin_user_deleted')->causer_id)->toBe($this->admin->id)
        ->and(panelActionEntry('account_deleted')->causer_id)->toBe($this->admin->id);
});

it('redirects to the list after deleting from the view page', function (): void {
    $target = User::factory()->create();

    Livewire::test(ViewUser::class, ['record' => $target->getRouteKey()])
        ->callAction('deleteAccount')
        ->assertRedirect(ListUsers::getUrl());

    $this->assertSoftDeleted($target);
});

it('keeps the account and shows the error when the Stripe cancellation fails', function (): void {
    $target = User::factory()->create();
    Subscription::factory()->for($target)->create(['stripe_subscription_id' => 'sub_target']);
    $this->fake->cancelException = new RuntimeException('stripe down');

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('deleteAccount')->table($target->fresh()))
        ->assertNotified('Não foi possível cancelar sua assinatura no Stripe, por isso sua conta não foi excluída. Tente novamente em instantes ou fale com o suporte.');

    $this->assertNotSoftDeleted($target);
    expect(panelActionEntry('admin_user_deleted'))->toBeNull();
});

it('deletes the account when Stripe no longer has the subscription', function (): void {
    $target = User::factory()->create();
    $subscription = Subscription::factory()->for($target)->create(['stripe_subscription_id' => 'sub_gone']);
    $this->fake->cancelException = new StripeResourceMissingException('gone');

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('deleteAccount')->table($target->fresh()))
        ->assertNotified('Conta excluída.');

    $this->assertSoftDeleted($target);
    expect(Subscription::withTrashed()->find($subscription->id)->status)->toBe(SubscriptionStatus::CANCELED);
});

it('refuses to delete your own account', function (): void {
    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('deleteAccount')->table($this->admin))
        ->assertNotified('Você não pode excluir a sua própria conta pelo painel.');

    $this->assertNotSoftDeleted($this->admin);
});
