<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Filament\Facades\Filament;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Users resource: list, search, filters and data minimisation on the view
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->admin = mfaAdmin(['name' => 'Admin Principal']);
    $this->actingAs($this->admin, 'admin');
});

it('lists users with plan, roles and status', function (): void {
    $pro = User::factory()->create(['name' => 'Paula Pro']);
    Subscription::factory()->for($pro)->create();
    $suspended = User::factory()->suspended()->create(['name' => 'Sérgio Suspenso']);

    Livewire::test(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->admin, $pro->fresh(), $suspended])
        ->assertSee('Profissional')
        ->assertSee('Administrador')
        ->assertSee('Suspensa');
});

it('searches by name, username and e-mail', function (): void {
    $ana = User::factory()->create(['name' => 'Ana Lima', 'username' => 'analima', 'email' => 'ana@example.com']);
    $bruno = User::factory()->create(['name' => 'Bruno Costa', 'username' => 'brunoc', 'email' => 'bruno@example.com']);

    Livewire::test(ListUsers::class)
        ->searchTable('Ana Lima')
        ->assertCanSeeTableRecords([$ana])
        ->assertCanNotSeeTableRecords([$bruno])
        ->searchTable('brunoc')
        ->assertCanSeeTableRecords([$bruno])
        ->assertCanNotSeeTableRecords([$ana])
        ->searchTable('bruno@example.com')
        ->assertCanSeeTableRecords([$bruno]);
});

it('filters by plan', function (): void {
    $pro = User::factory()->create();
    Subscription::factory()->for($pro)->create();
    $expired = User::factory()->create();
    Subscription::factory()->for($expired)->expired()->create();
    $free = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->filterTable('plan', 'professional')
        ->assertCanSeeTableRecords([$pro])
        ->assertCanNotSeeTableRecords([$expired, $free])
        ->filterTable('plan', 'free')
        ->assertCanSeeTableRecords([$expired, $free])
        ->assertCanNotSeeTableRecords([$pro]);
});

it('filters by role', function (): void {
    $plain = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->filterTable('role', 'admin')
        ->assertCanSeeTableRecords([$this->admin])
        ->assertCanNotSeeTableRecords([$plain])
        ->filterTable('role', 'user')
        ->assertCanSeeTableRecords([$plain]);
});

it('filters by e-mail verification', function (): void {
    $verified = User::factory()->create();
    $unverified = User::factory()->unverified()->create();

    Livewire::test(ListUsers::class)
        ->filterTable('email_verified_at', false)
        ->assertCanSeeTableRecords([$unverified])
        ->assertCanNotSeeTableRecords([$verified])
        ->filterTable('email_verified_at', true)
        ->assertCanSeeTableRecords([$verified])
        ->assertCanNotSeeTableRecords([$unverified]);
});

it('shows account and billing data but never financial data on the view page', function (): void {
    $user = User::factory()->create(['name' => 'Carla Cliente', 'email' => 'carla@example.com']);
    Subscription::factory()->for($user)->create();
    $wallet = Wallet::factory()->for($user)->create(['name' => 'Carteira Secreta da Carla', 'type' => 'checking', 'balance' => 98765.43]);
    Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'description' => 'Compra Sigilosa da Carla',
        'amount' => 4321.09,
        'date' => now()->toDateString(),
    ]);

    $this->get(ViewUser::getUrl(['record' => $user]))
        ->assertOk()
        ->assertSee('carla@example.com')
        ->assertSee('Profissional')
        ->assertSee('Ativa')
        ->assertDontSee('Carteira Secreta da Carla')
        ->assertDontSee('Compra Sigilosa da Carla')
        ->assertDontSee('98.765,43')
        ->assertDontSee('4.321,09');
});

it('does not list deleted accounts', function (): void {
    $deleted = User::factory()->create();
    $deleted->delete();

    Livewire::test(ListUsers::class)->assertCanNotSeeTableRecords([$deleted]);
});
