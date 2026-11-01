<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use Carbon\CarbonInterface;

/**
 * Single source of truth for the Professional plan price and optional promo.
 */
interface PlanPricingInterface
{
    /**
     * Regular monthly price of the Professional plan.
     */
    public function regularPrice(): float;

    /**
     * True only when the promo is enabled, cheaper than the regular price,
     * has a future end date and a Stripe price id to charge.
     */
    public function isPromoActive(): bool;

    /**
     * Promo price when the promo is active, otherwise null.
     */
    public function promoPrice(): ?float;

    /**
     * Promo end date when the promo is active, otherwise null.
     */
    public function promoEndsAt(): ?CarbonInterface;

    /**
     * Human label of the promo (pt-BR).
     */
    public function promoLabel(): string;

    /**
     * Price that is shown and charged right now.
     */
    public function currentPrice(): float;

    /**
     * Stripe price id used for new checkouts (promo id when active).
     */
    public function checkoutPriceId(): ?string;

    /**
     * Format an amount as Brazilian Real (e.g. "R$ 29,90").
     */
    public function format(float $value): string;

    public function formattedCurrent(): string;

    public function formattedRegular(): string;

    /**
     * Display name of the Professional plan.
     */
    public function planName(): string;
}
