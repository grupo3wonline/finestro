<?php

declare(strict_types=1);

use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Resources\Activities\Pages\ViewActivity;
use App\Filament\Resources\Activities\Schemas\ActivityInfolist;
use App\Interfaces\Services\SecurityActivityLoggerInterface;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

/*
|--------------------------------------------------------------------------
| Activity resource: filters, financial subjects hidden, security details
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->admin = mfaAdmin();
    $this->actingAs($this->admin, 'admin');
    Activity::query()->delete();
});

function securityEntry(string $event, User $subject, array $properties = [], ?User $causer = null): Activity
{
    app(SecurityActivityLoggerInterface::class)->log($event, $subject, $properties, $causer);

    return Activity::query()->where('event', $event)->latest('id')->firstOrFail();
}

it('filters by event, causer and period', function (): void {
    $ana = User::factory()->create(['name' => 'Ana Autora']);
    $bruno = User::factory()->create(['name' => 'Bruno Autor']);
    Activity::query()->delete();

    $login = securityEntry('login', $ana, [], $ana);
    $logout = securityEntry('logout', $bruno, [], $bruno);
    $old = securityEntry('password_changed', $ana, [], $ana);
    $old->forceFill(['created_at' => now()->subDays(10)])->save();

    Livewire::test(ListActivities::class)
        ->assertCanSeeTableRecords([$login, $logout, $old])
        ->filterTable('event', 'login')
        ->assertCanSeeTableRecords([$login])
        ->assertCanNotSeeTableRecords([$logout, $old])
        ->resetTableFilters()
        ->filterTable('causer', $bruno->id)
        ->assertCanSeeTableRecords([$logout])
        ->assertCanNotSeeTableRecords([$login, $old])
        ->resetTableFilters()
        ->filterTable('period', ['from' => now()->subDays(2)->toDateString(), 'until' => now()->toDateString()])
        ->assertCanSeeTableRecords([$login, $logout])
        ->assertCanNotSeeTableRecords([$old]);
});

it('hides the logged values of a financial record', function (): void {
    Carbon::setTestNow('2026-03-15 12:00:00');
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['name' => 'Carteira Oculta', 'type' => 'checking']);
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'description' => 'Compra Sigilosa',
        'amount' => 4321.09,
        'date' => '2026-03-14',
    ]);

    $activity = Activity::query()
        ->where('subject_type', $transaction->getMorphClass())
        ->where('subject_id', $transaction->id)
        ->where('event', 'created')
        ->firstOrFail();

    expect((string) json_encode($activity->attribute_changes))->toContain('Compra Sigilosa');

    $this->get(ViewActivity::getUrl(['record' => $activity]))
        ->assertOk()
        ->assertSee(ActivityInfolist::HIDDEN_NOTICE)
        ->assertSee('Transaction')
        ->assertDontSee('Compra Sigilosa')
        ->assertDontSee('4321.09')
        ->assertDontSee('Carteira Oculta');

    $this->get(ListActivities::getUrl())
        ->assertOk()
        ->assertDontSee('Compra Sigilosa')
        ->assertDontSee('Carteira Oculta');

    Carbon::setTestNow();
});

it('shows the stored properties of a security entry', function (): void {
    $user = User::factory()->create(['name' => 'Dona do Token']);
    $activity = securityEntry('api_token_created', $user, ['token_name' => 'Integração ERP'], $user);

    Livewire::test(ViewActivity::class, ['record' => $activity->getKey()])
        ->assertOk()
        ->assertSee('Criou um token de API')
        ->assertSee('token_name')
        ->assertSee('Integração ERP')
        ->assertSee('Dona do Token')
        ->assertDontSee(ActivityInfolist::HIDDEN_NOTICE);
});

it('labels admin events in pt-BR', function (): void {
    $target = User::factory()->create();
    securityEntry('admin_user_suspended', $target, ['via' => 'admin'], $this->admin);

    Livewire::test(ListActivities::class)
        ->assertSee('Admin: suspendeu a conta')
        ->assertSee($this->admin->name);
});
