<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuid, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'terms_accepted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the wallets for the user.
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * Get the transactions for the user.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the goals for the user.
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    /**
     * Get the active subscription for the user.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    /**
     * Get all subscriptions for the user.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get payment history for the user.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PaymentHistory::class);
    }

    /**
     * Get AI credentials for the user.
     */
    public function aiCredential(): HasOne
    {
        return $this->hasOne(AiCredential::class);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get the user's current plan name.
     */
    public function getCurrentPlanAttribute(): string
    {
        $subscription = $this->subscription;

        if ($subscription && $subscription->isProfessional()) {
            return 'professional';
        }

        return 'free';
    }

    /**
     * Check if user is on professional plan.
     */
    public function isProfessional(): bool
    {
        return $this->current_plan === 'professional';
    }

    /**
     * Check if user is on free plan.
     */
    public function isFree(): bool
    {
        return $this->current_plan === 'free';
    }

    /**
     * Get plan limits for the user.
     */
    public function getPlanLimitsAttribute(): array
    {
        if ($this->isProfessional()) {
            return [
                'wallets' => PHP_INT_MAX,
                'transactions_per_month' => PHP_INT_MAX,
                'goals' => PHP_INT_MAX,
                'simulators' => true,
                'reports_export' => true,
                'ai_insights' => true,
                'categories' => PHP_INT_MAX,
            ];
        }

        // Free plan limits
        return [
            'wallets' => 1,
            'transactions_per_month' => 100,
            'goals' => 2,
            'simulators' => false,
            'reports_export' => false,
            'ai_insights' => false,
            'categories' => 5,
        ];
    }

    /**
     * Check if user can create more of a resource.
     */
    public function canCreate(string $resource): bool
    {
        $limits = $this->plan_limits;

        return match ($resource) {
            'wallet' => $this->wallets()->count() < $limits['wallets'],
            'goal' => $this->goals()->count() < $limits['goals'],
            'transaction' => $this->transactions()
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->count() < $limits['transactions_per_month'],
            'category' => Category::where('user_id', $this->id)->count() < $limits['categories'],
            default => true,
        };
    }

    /**
     * Check if user has access to a feature.
     */
    public function hasFeature(string $feature): bool
    {
        $limits = $this->plan_limits;

        return match ($feature) {
            'simulators' => $limits['simulators'],
            'reports_export' => $limits['reports_export'],
            'ai_insights' => $limits['ai_insights'],
            default => true,
        };
    }
}
