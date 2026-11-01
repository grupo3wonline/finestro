<?php

namespace Tests\Feature\Billing;

use App\Interfaces\Services\PlanPricingInterface;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Goal;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Plan gating matrix: which subscription states grant Professional access,
 * and how every gated limit/feature behaves for Free vs Professional.
 */
class PlanGatingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, bool}>
     */
    public static function states(): array
    {
        return [
            'no subscription (Free)' => ['none', false],
            'active' => ['active', true],
            'pending cancellation' => ['pending', true],
            'canceled within period' => ['canceled', true],
            'expired' => ['expired', false],
            'past due' => ['past_due', false],
        ];
    }

    private function userIn(string $state): User
    {
        $user = User::factory()->create();
        $factory = Subscription::factory()->for($user);

        match ($state) {
            'none' => null,
            'active' => $factory->create(),
            'pending' => $factory->pendingCancellation()->create(),
            'canceled' => $factory->canceled()->create(['current_period_end' => now()->addDays(10)]),
            'expired' => $factory->expired()->create(),
            'past_due' => $factory->pastDue()->create(),
        };

        return $user->fresh();
    }

    #[DataProvider('states')]
    public function test_professional_access_by_state(string $state, bool $pro): void
    {
        $this->assertSame($pro, $this->userIn($state)->isProfessional());
    }

    #[DataProvider('states')]
    public function test_second_wallet(string $state, bool $pro): void
    {
        $user = $this->userIn($state);
        Wallet::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('wallets.store'), [
            'name' => 'Segunda Carteira',
            'type' => 'checking',
            'ownership' => 'personal',
            'currency' => 'BRL',
        ]);

        if ($pro) {
            $response->assertSessionHasNoErrors()->assertSessionMissing('error');
            $this->assertSame(2, $user->wallets()->count());
        } else {
            $response->assertRedirect(route('wallets.index'))->assertSessionHas('error');
            $this->assertSame(1, $user->wallets()->count());
        }
    }

    #[DataProvider('states')]
    public function test_third_goal(string $state, bool $pro): void
    {
        $user = $this->userIn($state);
        Goal::factory()->for($user)->count(2)->create();

        $response = $this->actingAs($user)->post(route('goals.store'), [
            'name' => 'Terceira meta',
            'target_amount' => 1000,
        ]);

        if ($pro) {
            $response->assertSessionHasNoErrors()->assertSessionMissing('error');
            $this->assertSame(3, $user->goals()->count());
        } else {
            $response->assertRedirect(route('goals.index'))->assertSessionHas('error');
            $this->assertSame(2, $user->goals()->count());
        }
    }

    #[DataProvider('states')]
    public function test_sixth_category(string $state, bool $pro): void
    {
        $user = $this->userIn($state);
        $missing = max(0, 5 - Category::where('user_id', $user->id)->count());
        Category::factory()->for($user)->count($missing)->create();

        $response = $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Sexta categoria',
            'type' => 'expense',
        ]);

        $count = Category::where('user_id', $user->id)->count();

        if ($pro) {
            $response->assertSessionHasNoErrors()->assertSessionMissing('error');
            $this->assertSame(6, $count);
        } else {
            $response->assertRedirect(route('categories.index'))->assertSessionHas('error');
            $this->assertSame(5, $count);
        }
    }

    #[DataProvider('states')]
    public function test_101st_transaction_this_month(string $state, bool $pro): void
    {
        $user = $this->userIn($state);
        $wallet = Wallet::factory()->for($user)->create(['type' => 'checking', 'balance' => 1000]);
        Transaction::factory()->count(100)->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'date' => now()->toDateString(),
            'is_recurring' => false,
        ]);

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'wallet_id' => $wallet->id,
            'type' => 'expense',
            'amount' => '10.00',
            'description' => 'Transação 101',
            'date' => now()->toDateString(),
        ]);

        $count = Transaction::where('user_id', $user->id)->count();

        if ($pro) {
            $response->assertSessionHasNoErrors()->assertSessionMissing('error');
            $this->assertSame(101, $count);
        } else {
            $response->assertRedirect(route('transactions.index'))->assertSessionHas('error');
            $this->assertSame(100, $count);
        }
    }

    #[DataProvider('states')]
    public function test_fourth_budget_this_month(string $state, bool $pro): void
    {
        $user = $this->userIn($state);
        $categories = Category::factory()->for($user)->count(4)->create(['type' => 'expense']);

        foreach ($categories->take(3) as $category) {
            Budget::factory()->for($user)->forCategory($category->id)->create();
        }

        $response = $this->actingAs($user)->post(route('budgets.store'), [
            'category_id' => $categories[3]->id,
            'year' => now()->year,
            'month' => now()->month,
            'amount' => '100',
        ]);

        $count = Budget::where('user_id', $user->id)->where('year', now()->year)->where('month', now()->month)->count();

        if ($pro) {
            $response->assertSessionHasNoErrors()->assertSessionMissing('error');
            $this->assertSame(4, $count);
        } else {
            $response->assertSessionHas('error');
            $this->assertSame(3, $count);
        }
    }

    #[DataProvider('states')]
    public function test_simulators(string $state, bool $pro): void
    {
        $response = $this->actingAs($this->userIn($state))->get(route('simulators.index'));

        $response->assertOk()->assertViewIs($pro ? 'simulators.index' : 'simulators.locked');
    }

    #[DataProvider('states')]
    public function test_reports_export(string $state, bool $pro): void
    {
        $response = $this->actingAs($this->userIn($state))->get(route('reports.export'));

        if ($pro) {
            $response->assertOk();
        } else {
            $response->assertRedirect(route('reports.index'))->assertSessionHas('error');
        }
    }

    #[DataProvider('states')]
    public function test_ai_insights(string $state, bool $pro): void
    {
        $response = $this->actingAs($this->userIn($state))->get(route('ai.index'));

        if ($pro) {
            $response->assertOk()->assertViewIs('ai.index');
        } else {
            $response->assertRedirect(route('subscription.plans'));
        }
    }

    #[DataProvider('states')]
    public function test_wallet_sharing(string $state, bool $pro): void
    {
        Mail::fake();
        Notification::fake();

        $user = $this->userIn($state);
        $wallet = Wallet::factory()->for($user)->create(['ownership' => 'business']);

        $response = $this->actingAs($user)->post(route('wallets.share', $wallet), [
            'email' => 'convidado@example.com',
            'role' => 'viewer',
        ]);

        if ($pro) {
            $this->assertNotSame(403, $response->status());
            $response->assertSessionHasNoErrors();
        } else {
            $response->assertForbidden();
        }
    }

    public function test_plan_limit_middleware_message_uses_configured_price(): void
    {
        Route::middleware(['web', 'auth', 'plan.limit:feature,simulators'])
            ->get('/_plan-limit-probe', fn () => 'ok');

        $user = $this->userIn('none');

        $response = $this->actingAs($user)->from(route('dashboard'))->get('/_plan-limit-probe');

        $response->assertRedirect(route('dashboard'));
        $this->assertStringContainsString(
            app(PlanPricingInterface::class)->formattedCurrent(),
            (string) session('error'),
        );

        $pro = $this->userIn('active');
        $this->actingAs($pro)->get('/_plan-limit-probe')->assertOk()->assertSee('ok');
    }
}
