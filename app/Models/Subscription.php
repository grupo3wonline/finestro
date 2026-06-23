<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasUuid, SoftDeletes;

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
        'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentHistory::class);
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        // Must be in active/trialing status
        if (!in_array($this->status, ['active', 'trialing'])) {
            return false;
        }

        // Must not be past the end of the current period
        if ($this->current_period_end && $this->current_period_end->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if subscription is on professional plan.
     */
    public function isProfessional(): bool
    {
        return $this->plan === 'professional' && $this->isActive();
    }

    /**
     * Check if subscription is expired (past period end).
     */
    public function isExpired(): bool
    {
        if (!$this->current_period_end) {
            return false;
        }

        return $this->current_period_end->isPast();
    }

    /**
     * Check if subscription needs renewal (expired but not canceled).
     */
    public function needsRenewal(): bool
    {
        return $this->isExpired() && $this->status !== 'canceled';
    }

    /**
     * Get days remaining until renewal/expiration.
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->current_period_end) {
            return null;
        }

        return max(0, now()->diffInDays($this->current_period_end, false));
    }
}
