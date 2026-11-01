<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Traits\HasUuid;
use App\Traits\LogsOwnedActivity;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The column is NOT NULL (default 'active'), but an unsaved instance has no
 * status yet, so the accessors below keep their null-safe calls.
 *
 * @property SubscriptionStatus|null $status
 */
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory, HasUuid, LogsOwnedActivity, SoftDeletes;

    /**
     * Attributes recorded in the activity log (plan changes). Stripe ids are
     * never logged.
     *
     * @return list<string>
     */
    protected function activityLogAttributes(): array
    {
        return ['plan', 'status', 'cancel_at_period_end', 'current_period_end', 'canceled_at'];
    }

    protected $fillable = [
        'user_id',
        'stripe_subscription_id',
        'stripe_customer_id',
        'stripe_price_id',
        'plan',
        'status',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'cancel_at_period_end',
        'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancel_at_period_end' => 'boolean',
            'canceled_at' => 'datetime',
            'status' => SubscriptionStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<PaymentHistory, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(PaymentHistory::class);
    }

    /**
     * Check if subscription grants access right now.
     *
     * Active/trialing within the period, or canceled with access until a
     * future period end. past_due/incomplete fall back to Free.
     */
    public function isActive(): bool
    {
        if ($this->current_period_end && $this->current_period_end->isPast()) {
            return false;
        }

        if ($this->status?->grantsAccess()) {
            return true;
        }

        return $this->status === SubscriptionStatus::CANCELED && $this->current_period_end !== null;
    }

    /**
     * Cancellation is scheduled for period end but access is still granted.
     */
    public function hasPendingCancellation(): bool
    {
        return (bool) $this->cancel_at_period_end && $this->isActive();
    }

    /**
     * Status label in pt-BR for the UI.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->isExpired()) {
            return 'Expirada';
        }

        if ($this->hasPendingCancellation()) {
            return 'Cancelamento agendado';
        }

        return $this->status?->label() ?? '';
    }

    /**
     * Check if subscription is on professional plan.
     */
    public function isProfessional(): bool
    {
        return $this->plan === 'professional' && $this->isActive();
    }

    /**
     * SQL mirror of isProfessional(): Professional plan, period not over, and
     * an access-granting status (or canceled with a period end still ahead).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeGrantingProfessional(Builder $query): Builder
    {
        return $query->where('plan', 'professional')
            ->where(fn (Builder $period) => $period
                ->whereNull('current_period_end')
                ->orWhere('current_period_end', '>', now()))
            ->where(fn (Builder $status) => $status
                ->whereIn('status', array_map(fn (SubscriptionStatus $s): string => $s->value, SubscriptionStatus::granting()))
                ->orWhere(fn (Builder $canceled) => $canceled
                    ->where('status', SubscriptionStatus::CANCELED->value)
                    ->whereNotNull('current_period_end')));
    }

    /**
     * Check if subscription is expired (past period end).
     */
    public function isExpired(): bool
    {
        if (! $this->current_period_end) {
            return false;
        }

        return $this->current_period_end->isPast();
    }

    /**
     * Check if subscription needs renewal (expired but not canceled).
     */
    public function needsRenewal(): bool
    {
        return $this->isExpired() && $this->status !== SubscriptionStatus::CANCELED;
    }

    /**
     * The subscription still exists in Stripe (it can be charged or canceled there).
     */
    public function isOpenInStripe(): bool
    {
        return ! empty($this->stripe_subscription_id)
            && ! in_array($this->status, [SubscriptionStatus::CANCELED, SubscriptionStatus::INCOMPLETE_EXPIRED], true);
    }

    /**
     * A renewal charge failed and Stripe is still retrying it.
     */
    public function isPaymentPending(): bool
    {
        return in_array($this->status, [SubscriptionStatus::PAST_DUE, SubscriptionStatus::UNPAID], true);
    }

    /**
     * Message explaining why a non-Professional subscription cannot see the
     * confirmation page, or null when access is granted.
     *
     * Centralizes the business rule so the controller stays thin and the rule
     * is unit-testable without the HTTP layer.
     */
    public function ineligibleAccessMessage(): ?string
    {
        if ($this->isProfessional()) {
            return null;
        }

        return match (true) {
            $this->isExpired() => 'Sua assinatura expirou. Assine novamente para voltar ao plano Profissional.',
            $this->isPaymentPending() => 'Pagamento pendente. Não conseguimos confirmar a cobrança desta assinatura; regularize o pagamento ou cancele na página Financeiro.',
            in_array($this->status, [SubscriptionStatus::INCOMPLETE, SubscriptionStatus::INCOMPLETE_EXPIRED], true) => 'Pagamento ainda não confirmado. Assim que o Stripe aprovar a cobrança, o plano Profissional será ativado.',
            default => 'Esta assinatura já foi encerrada. Assine novamente para voltar ao plano Profissional.',
        };
    }

    /**
     * Get days remaining until renewal/expiration.
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if (! $this->current_period_end) {
            return null;
        }

        return (int) max(0, now()->diffInDays($this->current_period_end, false));
    }
}
