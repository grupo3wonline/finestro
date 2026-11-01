<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\Services\PlanPricingInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Reads plan price and promo settings from config/stripe.php. This is the only
 * class allowed to read those keys, so displayed and charged prices match.
 */
class PlanPricing implements PlanPricingInterface
{
    public function regularPrice(): float
    {
        return (float) config('stripe.plans.professional.price', 0);
    }

    public function isPromoActive(): bool
    {
        if (! config('stripe.promo.enabled')) {
            return false;
        }

        $price = $this->rawPromoPrice();

        if ($price === null || $price <= 0 || $price >= $this->regularPrice()) {
            return false;
        }

        $endsAt = $this->parsedPromoEnd();

        if (! $endsAt instanceof CarbonInterface || ! $endsAt->isFuture()) {
            return false;
        }

        return filled(config('stripe.promo.stripe_price_id'));
    }

    public function promoPrice(): ?float
    {
        return $this->isPromoActive() ? $this->rawPromoPrice() : null;
    }

    public function promoEndsAt(): ?CarbonInterface
    {
        return $this->isPromoActive() ? $this->parsedPromoEnd() : null;
    }

    public function promoLabel(): string
    {
        return (string) (config('stripe.promo.label') ?: 'Oferta especial');
    }

    public function currentPrice(): float
    {
        return $this->promoPrice() ?? $this->regularPrice();
    }

    public function checkoutPriceId(): ?string
    {
        $priceId = $this->isPromoActive()
            ? config('stripe.promo.stripe_price_id')
            : config('stripe.plans.professional.stripe_price_id');

        return filled($priceId) ? (string) $priceId : null;
    }

    public function format(float $value): string
    {
        return 'R$ '.number_format($value, 2, ',', '.');
    }

    public function formattedCurrent(): string
    {
        return $this->format($this->currentPrice());
    }

    public function formattedRegular(): string
    {
        return $this->format($this->regularPrice());
    }

    public function planName(): string
    {
        return (string) config('stripe.plans.professional.name', 'Profissional');
    }

    private function rawPromoPrice(): ?float
    {
        $price = config('stripe.promo.price');

        return is_numeric($price) ? (float) $price : null;
    }

    private function parsedPromoEnd(): ?CarbonInterface
    {
        $endsAt = config('stripe.promo.ends_at');

        if (! filled($endsAt)) {
            return null;
        }

        try {
            return Carbon::parse((string) $endsAt);
        } catch (\Throwable) {
            return null;
        }
    }
}
