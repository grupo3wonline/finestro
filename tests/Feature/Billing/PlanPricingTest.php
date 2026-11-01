<?php

namespace Tests\Feature\Billing;

use App\Interfaces\Services\PlanPricingInterface;
use Tests\TestCase;

/**
 * Price comes only from config/stripe.php, and the promo is shown/charged
 * only when every promo condition holds.
 */
class PlanPricingTest extends TestCase
{
    private function pricing(): PlanPricingInterface
    {
        return $this->app->make(PlanPricingInterface::class);
    }

    private function activePromo(array $overrides = []): void
    {
        config(['stripe.promo' => array_replace([
            'enabled' => true,
            'price' => '19.90',
            'ends_at' => now()->addDays(3)->toIso8601String(),
            'stripe_price_id' => 'price_test_promo',
            'label' => 'Oferta de lançamento',
        ], $overrides)]);
    }

    public function test_default_price_is_29_90(): void
    {
        $this->assertSame(29.90, $this->pricing()->regularPrice());
        $this->assertSame(29.90, $this->pricing()->currentPrice());
        $this->assertSame('R$ 29,90', $this->pricing()->formattedCurrent());
        $this->assertSame('R$ 29,90', $this->pricing()->formattedRegular());
        $this->assertSame('Profissional', $this->pricing()->planName());
    }

    public function test_price_can_be_overridden_by_config(): void
    {
        config(['stripe.plans.professional.price' => 1234.5]);

        $this->assertSame('R$ 1.234,50', $this->pricing()->formattedCurrent());
    }

    public function test_promo_is_off_by_default(): void
    {
        $this->assertFalse($this->pricing()->isPromoActive());
        $this->assertNull($this->pricing()->promoPrice());
        $this->assertNull($this->pricing()->promoEndsAt());
        $this->assertSame('price_test_professional', $this->pricing()->checkoutPriceId());
    }

    public function test_promo_is_active_only_when_all_conditions_hold(): void
    {
        $this->activePromo();

        $pricing = $this->pricing();

        $this->assertTrue($pricing->isPromoActive());
        $this->assertSame(19.90, $pricing->promoPrice());
        $this->assertSame('R$ 19,90', $pricing->formattedCurrent());
        $this->assertSame('R$ 29,90', $pricing->formattedRegular());
        $this->assertSame('Oferta de lançamento', $pricing->promoLabel());
        $this->assertTrue($pricing->promoEndsAt()->isFuture());
        $this->assertSame('price_test_promo', $pricing->checkoutPriceId());
    }

    public function test_promo_disabled_flag_turns_it_off(): void
    {
        $this->activePromo(['enabled' => false]);

        $this->assertFalse($this->pricing()->isPromoActive());
    }

    public function test_promo_with_past_end_date_is_off(): void
    {
        $this->activePromo(['ends_at' => now()->subMinute()->toIso8601String()]);

        $this->assertFalse($this->pricing()->isPromoActive());
        $this->assertSame(29.90, $this->pricing()->currentPrice());
    }

    public function test_promo_with_invalid_end_date_is_off(): void
    {
        $this->activePromo(['ends_at' => 'not-a-date']);

        $this->assertFalse($this->pricing()->isPromoActive());
    }

    public function test_promo_without_stripe_price_id_is_off(): void
    {
        $this->activePromo(['stripe_price_id' => null]);

        $this->assertFalse($this->pricing()->isPromoActive());
        $this->assertSame('price_test_professional', $this->pricing()->checkoutPriceId());
    }

    public function test_promo_not_cheaper_than_regular_is_off(): void
    {
        $this->activePromo(['price' => '29.90']);
        $this->assertFalse($this->pricing()->isPromoActive());

        $this->activePromo(['price' => '0']);
        $this->assertFalse($this->pricing()->isPromoActive());
    }
}
