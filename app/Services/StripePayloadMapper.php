<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use Carbon\Carbon;

/**
 * Reads Stripe objects (API 2026-04-22.dahlia first, legacy fields as
 * fallback) into plain arrays. Every read uses `?? null` so missing keys on
 * StripeObject never raise notices.
 */
final class StripePayloadMapper
{
    /**
     * Map a Stripe subscription status to the local status enum.
     */
    public static function mapStatus(?string $status): SubscriptionStatus
    {
        return match ($status) {
            'active' => SubscriptionStatus::ACTIVE,
            'trialing' => SubscriptionStatus::TRIALING,
            'past_due', 'unpaid', 'paused' => SubscriptionStatus::PAST_DUE,
            'canceled', 'incomplete_expired' => SubscriptionStatus::CANCELED,
            default => SubscriptionStatus::INCOMPLETE,
        };
    }

    /**
     * @return array{start: ?Carbon, end: ?Carbon}
     */
    public static function subscriptionPeriod(object $subscription): array
    {
        $item = $subscription->items->data[0] ?? null;

        return [
            'start' => self::timestamp($item->current_period_start ?? $subscription->current_period_start ?? null),
            'end' => self::timestamp($item->current_period_end ?? $subscription->current_period_end ?? null),
        ];
    }

    public static function invoiceSubscriptionId(object $invoice): ?string
    {
        return self::id(
            $invoice->parent->subscription_details->subscription
                ?? $invoice->subscription
                ?? null
        );
    }

    /**
     * @return array{id: ?string, amount_paid: ?int, amount_due: ?int, currency: string, status: ?string, hosted_invoice_url: ?string, invoice_pdf: ?string, paid_at: ?Carbon, period_start: ?Carbon, period_end: ?Carbon}
     */
    public static function invoiceData(object $invoice): array
    {
        $period = $invoice->lines->data[0]->period ?? null;

        return [
            'id' => self::id($invoice->id ?? null),
            'amount_paid' => isset($invoice->amount_paid) ? (int) $invoice->amount_paid : null,
            'amount_due' => isset($invoice->amount_due) ? (int) $invoice->amount_due : null,
            'currency' => strtoupper((string) ($invoice->currency ?? config('stripe.currency', 'BRL'))),
            'status' => isset($invoice->status) ? (string) $invoice->status : null,
            'hosted_invoice_url' => isset($invoice->hosted_invoice_url) ? (string) $invoice->hosted_invoice_url : null,
            'invoice_pdf' => isset($invoice->invoice_pdf) ? (string) $invoice->invoice_pdf : null,
            'paid_at' => self::timestamp($invoice->status_transitions->paid_at ?? null),
            'period_start' => self::timestamp($period->start ?? null),
            'period_end' => self::timestamp($period->end ?? null),
        ];
    }

    /**
     * @return array{id: ?string, status: SubscriptionStatus, price_id: ?string, cancel_at_period_end: bool, period_start: ?Carbon, period_end: ?Carbon, canceled_at: ?Carbon, ended_at: ?Carbon}
     */
    public static function subscriptionData(object $subscription): array
    {
        $period = self::subscriptionPeriod($subscription);

        return [
            'id' => self::id($subscription->id ?? null),
            'status' => self::mapStatus($subscription->status ?? null),
            'price_id' => self::id($subscription->items->data[0]->price ?? null),
            'cancel_at_period_end' => (bool) ($subscription->cancel_at_period_end ?? false),
            'period_start' => $period['start'],
            'period_end' => $period['end'],
            'canceled_at' => self::timestamp($subscription->canceled_at ?? null),
            'ended_at' => self::timestamp($subscription->ended_at ?? null),
        ];
    }

    /**
     * Stringify an id that may be an expanded object or a plain string.
     */
    public static function id(mixed $value): ?string
    {
        if (is_object($value)) {
            $value = $value->id ?? null;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function timestamp(mixed $value): ?Carbon
    {
        if (! is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $value, config('app.timezone'));
    }
}
