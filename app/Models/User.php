<?php

namespace App\Models;

use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use App\Traits\HasUuid;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuid, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, Notifiable, SoftDeletes;

    /**
     * Shown on the user-app login, on logout of an active session and in
     * the API 403 envelope when the account is suspended.
     */
    public const string SUSPENDED_MESSAGE = 'Sua conta está suspensa. Entre em contato com o suporte para mais informações.';

    /**
     * Spatie roles/permissions always live on the `web` guard. Filament calls
     * Auth::shouldUse('admin') inside the panel; without this pin Spatie would
     * look up `admin`-guard roles and throw PermissionDoesNotExist.
     */
    protected string $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * Note: the `role` column is legacy. It is intentionally NOT mass
     * assignable to prevent privilege escalation. Authorization uses
     * Spatie roles/permissions exclusively; the legacy column is written
     * only by the factory (via Model::unguarded()) and read only by
     * RolesAndPermissionsSeeder to migrate pre-existing users.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'terms_accepted_at',
        'terms_version',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * The MFA columns are cast `encrypted` / `encrypted:array` by the
     * Filament InteractsWithAppAuthentication* traits.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'suspended_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Whether an administrator suspended this account.
     */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * Only active, verified admins holding `admin.access` may enter /admin.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin'
            && ! $this->isSuspended()
            && $this->hasVerifiedEmail()
            && $this->hasRole('admin')
            && $this->hasPermissionTo('admin.access');
    }

    /**
     * Send the e-mail verification link through the queue.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new QueuedVerifyEmail);
    }

    /**
     * Send the password reset link through the queue.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new QueuedResetPassword($token));
    }

    /**
     * Get the wallets for the user.
     *
     * @return HasMany<Wallet, $this>
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * Get the transactions for the user.
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the monthly budgets for the user.
     *
     * @return HasMany<Budget, $this>
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * Get the goals for the user.
     *
     * @return HasMany<Goal, $this>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    /**
     * Get the active subscription for the user.
     *
     * @return HasOne<Subscription, $this>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    /**
     * Get all subscriptions for the user.
     *
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get payment history for the user.
     *
     * @return HasMany<PaymentHistory, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PaymentHistory::class);
    }

    /**
     * Get AI credentials for the user.
     *
     * @return HasOne<AiCredential, $this>
     */
    public function aiCredential(): HasOne
    {
        return $this->hasOne(AiCredential::class);
    }

    /**
     * Cookie consent decisions (proof of consent), newest last.
     *
     * @return HasMany<CookieConsent, $this>
     */
    public function cookieConsents(): HasMany
    {
        return $this->hasMany(CookieConsent::class);
    }

    /**
     * Personal data exports requested by the user.
     *
     * @return HasMany<DataExport, $this>
     */
    public function dataExports(): HasMany
    {
        return $this->hasMany(DataExport::class);
    }

    /**
     * Check if user is admin (Spatie role).
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
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
     *
     * @return array{wallets: int, transactions_per_month: int, goals: int, simulators: bool, reports_export: bool, ai_insights: bool, wallet_sharing: bool, api_access: bool, categories: int, budgets_per_month: int}
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
                'wallet_sharing' => true,
                'api_access' => true,
                'categories' => PHP_INT_MAX,
                'budgets_per_month' => PHP_INT_MAX,
            ];
        }

        return self::freePlanLimits();
    }

    /**
     * Free plan limits. Static so public/guest contexts (e.g. the landing
     * pricing table) can read the same numbers without a User instance,
     * keeping a single source of truth with enforcement.
     *
     * @return array{wallets: int, transactions_per_month: int, goals: int, simulators: bool, reports_export: bool, ai_insights: bool, wallet_sharing: bool, api_access: bool, categories: int, budgets_per_month: int}
     */
    public static function freePlanLimits(): array
    {
        return [
            'wallets' => 1,
            'transactions_per_month' => 100,
            'goals' => 2,
            'simulators' => false,
            'reports_export' => false,
            'ai_insights' => false,
            'wallet_sharing' => false,
            'api_access' => false,
            'categories' => 5,
            'budgets_per_month' => 3,
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
            'transaction' => $this->transactionCountInMonth(now()) < $limits['transactions_per_month'],
            'category' => Category::where('user_id', $this->id)->count() < $limits['categories'],
            'budget' => $this->budgets()
                ->where('year', now()->year)
                ->where('month', now()->month)
                ->count() < $limits['budgets_per_month'],
            default => true,
        };
    }

    /**
     * Number of the user's transactions dated in the month of $date.
     */
    public function transactionCountInMonth(CarbonInterface $date): int
    {
        return $this->transactions()
            ->whereMonth('date', $date->month)
            ->whereYear('date', $date->year)
            ->count();
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
            'wallet_sharing' => $limits['wallet_sharing'],
            'api_access' => $limits['api_access'],
            default => true,
        };
    }
}
