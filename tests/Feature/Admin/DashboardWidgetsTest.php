<?php

declare(strict_types=1);

use App\Filament\Widgets\AdminStatsOverview;
use App\Filament\Widgets\StripeConfigWarning;
use App\Models\PaymentHistory;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Dashboard widgets: stats values and the Stripe test-key warning
|--------------------------------------------------------------------------
*/

const ADMIN_WIDGET_FAKE_KEY = 'sk_test_x';

beforeEach(function (): void {
    Carbon::setTestNow('2026-03-15 12:00:00');
    config(['stripe.plans.professional.price' => 29.90]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->admin = mfaAdmin();
    $this->actingAs($this->admin, 'admin');
});

afterEach(function (): void {
    app()->detectEnvironment(fn (): string => 'testing');
    Carbon::setTestNow();
});

it('shows the dashboard numbers', function (): void {
    $pro = User::factory()->create();
    Subscription::factory()->for($pro)->create();
    Subscription::factory()->for(User::factory())->pastDue()->create();
    User::factory()->create();
    PaymentHistory::factory()->for($pro)->create(['amount' => 29.90, 'paid_at' => now()->subDay()]);

    Livewire::test(AdminStatsOverview::class)
        ->assertOk()
        ->assertSee('Usuários')
        ->assertSee('4')
        ->assertSee('3 × 1')
        ->assertSee('MRR estimado')
        ->assertSee('R$ 29,90')
        ->assertSee('1 com pagamento pendente')
        ->assertSee('1 pagamentos confirmados');
});

it('hides the Stripe warning outside production', function (): void {
    config(['stripe.secret_key' => ADMIN_WIDGET_FAKE_KEY]);

    expect(StripeConfigWarning::canView())->toBeFalse();

    $this->get('/admin')
        ->assertOk()
        ->assertDontSee('Stripe em modo de teste em produção');
});

it('hides the Stripe warning in production with a live key', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    config(['stripe.secret_key' => 'sk_live_x']);

    expect(StripeConfigWarning::canView())->toBeFalse();
});

it('shows the Stripe warning in production with a test key, never the key', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    config(['stripe.secret_key' => ADMIN_WIDGET_FAKE_KEY]);

    expect(StripeConfigWarning::canView())->toBeTrue();

    Livewire::test(StripeConfigWarning::class)
        ->assertOk()
        ->assertSee('Stripe em modo de teste em produção')
        ->assertDontSee(ADMIN_WIDGET_FAKE_KEY);

    $this->get('/admin')
        ->assertOk()
        ->assertSee('Stripe em modo de teste em produção')
        ->assertDontSee(ADMIN_WIDGET_FAKE_KEY);
});

it('never shows the widgets to a plain user', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    config(['stripe.secret_key' => ADMIN_WIDGET_FAKE_KEY]);
    $this->actingAs(User::factory()->create(), 'admin');

    expect(AdminStatsOverview::canView())->toBeFalse()
        ->and(StripeConfigWarning::canView())->toBeFalse();
});
