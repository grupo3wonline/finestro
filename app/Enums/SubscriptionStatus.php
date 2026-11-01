<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle status of a Stripe subscription, mirrored from Stripe's own
 * subscription statuses.
 *
 * - ACTIVE:              paid and granting access.
 * - TRIALING:            in a trial period, granting access.
 * - PAST_DUE:            a renewal payment failed; access falls back to Free.
 * - UNPAID:              repeated payment failure; access revoked.
 * - CANCELED:            ended (may still grant access until period end).
 * - INCOMPLETE:          first payment not yet completed.
 * - INCOMPLETE_EXPIRED:  first payment window elapsed without success.
 */
enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case TRIALING = 'trialing';
    case PAST_DUE = 'past_due';
    case UNPAID = 'unpaid';
    case CANCELED = 'canceled';
    case INCOMPLETE = 'incomplete';
    case INCOMPLETE_EXPIRED = 'incomplete_expired';

    /**
     * Statuses that, within a valid period, grant Professional access.
     *
     * @return array<int, self>
     */
    public static function granting(): array
    {
        return [self::ACTIVE, self::TRIALING];
    }

    /**
     * Status label in pt-BR for the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Ativa',
            self::TRIALING => 'Em período de teste',
            self::PAST_DUE => 'Pagamento pendente',
            self::UNPAID => 'Pagamento em atraso',
            self::CANCELED => 'Cancelada',
            self::INCOMPLETE => 'Incompleta',
            self::INCOMPLETE_EXPIRED => 'Incompleta (expirada)',
        };
    }

    /**
     * Whether this status (ignoring the period) is one of the access-granting
     * states.
     */
    public function grantsAccess(): bool
    {
        return in_array($this, self::granting(), true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
