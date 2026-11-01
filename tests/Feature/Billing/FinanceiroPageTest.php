<?php

namespace Tests\Feature\Billing;

use App\Interfaces\Services\PlanPricingInterface;
use App\Models\PaymentHistory;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Financeiro page (subscription.index): own data only, pagination, validity,
 * countdown, plan management forms, receipts and config-driven prices.
 */
class FinanceiroPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_only_own_payments(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        PaymentHistory::factory()->for($me)->create(['description' => 'Pagamento Meu Exclusivo']);
        PaymentHistory::factory()->for($other)->create(['description' => 'Pagamento Alheio Exclusivo']);

        $this->actingAs($me)->get(route('subscription.index'))
            ->assertOk()
            ->assertViewIs('subscription.index')
            ->assertSee('Pagamento Meu Exclusivo')
            ->assertDontSee('Pagamento Alheio Exclusivo');
    }

    public function test_payment_history_is_paginated_ten_per_page(): void
    {
        $me = User::factory()->create();

        foreach (range(1, 15) as $i) {
            PaymentHistory::factory()->for($me)->create([
                'description' => sprintf('Fatura-%02d', $i),
                'paid_at' => now()->subDays($i),
            ]);
        }

        $first = $this->actingAs($me)->get(route('subscription.index'));
        $first->assertOk()
            ->assertSee('Fatura-01')
            ->assertSee('Fatura-10')
            ->assertDontSee('Fatura-11');
        $this->assertCount(10, $first->viewData('payments'));

        $this->actingAs($me)->get(route('subscription.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Fatura-11')
            ->assertSee('Fatura-15')
            ->assertDontSee('Fatura-10');
    }

    public function test_shows_validity_dates_and_countdown(): void
    {
        $me = User::factory()->create();
        $start = now()->subDays(5)->startOfMinute();
        $end = now()->addDays(25)->startOfMinute();

        Subscription::factory()->for($me)->create([
            'current_period_start' => $start,
            'current_period_end' => $end,
        ]);

        $response = $this->actingAs($me)->get(route('subscription.index'));

        $response->assertOk()
            ->assertSee($start->format('d/m/Y'))
            ->assertSee($end->format('d/m/Y'))
            ->assertSee("billingCountdown('".$end->toIso8601String()."', false)", false)
            ->assertSee('Ativa');
    }

    public function test_active_subscription_shows_cancel_form_with_sweetalert_confirm(): void
    {
        $me = User::factory()->create();
        $end = now()->addDays(20);
        Subscription::factory()->for($me)->create(['current_period_end' => $end]);

        $this->actingAs($me)->get(route('subscription.index'))
            ->assertOk()
            ->assertSee(route('subscription.cancel'), false)
            ->assertSee('data-confirm="Seu plano Profissional continua ativo até '.$end->format('d/m/Y'), false)
            ->assertDontSee('return confirm(', false);
    }

    public function test_pending_cancellation_shows_label_and_resume_form(): void
    {
        $me = User::factory()->create();
        Subscription::factory()->for($me)->pendingCancellation()->create();

        $this->actingAs($me)->get(route('subscription.index'))
            ->assertOk()
            ->assertSee('Cancelamento agendado')
            ->assertSee('action="'.route('subscription.resume').'"', false)
            ->assertSee('Manter meu plano Profissional')
            ->assertDontSee('action="'.route('subscription.cancel').'"', false);
    }

    public function test_receipt_link_is_rendered_when_available(): void
    {
        $me = User::factory()->create();
        PaymentHistory::factory()->for($me)->create([
            'hosted_invoice_url' => 'https://invoice.stripe.test/i/receipt_abc',
        ]);

        $this->actingAs($me)->get(route('subscription.index'))
            ->assertOk()
            ->assertSee('href="https://invoice.stripe.test/i/receipt_abc"', false)
            ->assertSee('rel="noopener noreferrer"', false);
    }

    public function test_header_has_financeiro_link(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Financeiro')
            ->assertSee('href="'.route('subscription.index').'"', false);
    }

    public function test_price_follows_config(): void
    {
        config(['stripe.plans.professional.price' => 39.9]);
        $me = User::factory()->create();

        $this->actingAs($me)->get(route('subscription.index'))
            ->assertOk()
            ->assertSee('R$ 39,90');

        $this->actingAs($me)->get(route('subscription.plans'))
            ->assertOk()
            ->assertSee('R$ 39,90');
    }

    public function test_subscriber_sees_the_amount_of_the_latest_paid_invoice(): void
    {
        config(['stripe.plans.professional.price' => 39.9]);
        $me = User::factory()->create();
        $subscription = Subscription::factory()->for($me)->create(['current_period_end' => now()->addDays(20)]);

        PaymentHistory::factory()->for($me)->create([
            'subscription_id' => $subscription->id,
            'amount' => 19.90,
            'status' => 'paid',
            'paid_at' => now()->subDays(10),
        ]);

        $this->actingAs($me)->get(route('subscription.index'))
            ->assertOk()
            ->assertViewHas('chargedPrice', 19.90)
            ->assertSee('por R$ 19,90/mês')
            ->assertDontSee('R$ 39,90');
    }

    public function test_active_promo_renders_real_end_date_countdown(): void
    {
        $endsAt = now()->addDays(3)->startOfMinute();
        config(['stripe.promo' => [
            'enabled' => true,
            'price' => '19.90',
            'ends_at' => $endsAt->toIso8601String(),
            'stripe_price_id' => 'price_test_promo',
            'label' => 'Oferta de lançamento',
        ]]);

        $this->actingAs(User::factory()->create())->get(route('subscription.plans'))
            ->assertOk()
            ->assertSee('Oferta de lançamento')
            ->assertSee('R$ 19,90')
            ->assertSee('data-promo-ends-at="'.$endsAt->toIso8601String().'"', false);

        $this->get(route('home'))->assertOk()->assertSee('Oferta de lançamento');
    }

    public function test_promo_countdown_is_hidden_when_promo_is_off(): void
    {
        $this->actingAs(User::factory()->create())->get(route('subscription.plans'))
            ->assertOk()
            ->assertDontSee('data-promo-ends-at', false);
    }

    public function test_landing_shows_configured_price_and_no_fake_anchor(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee(app(PlanPricingInterface::class)->formattedCurrent())
            ->assertDontSee('41,58')
            ->assertDontSee('40% OFF');
    }
}
