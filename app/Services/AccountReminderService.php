<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\Services\AccountReminderServiceInterface;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PasswordExpiringNotification;
use App\Notifications\SubscriptionRenewalNotification;

class AccountReminderService implements AccountReminderServiceInterface
{
    public function remindPasswordExpiry(): int
    {
        $maxAge = (int) config('security.password_max_age_days');
        $warning = (int) config('security.password_warning_days');

        $sent = 0;

        // Window: password older than (max - warning) days, not yet expired.
        // The reminder is sent once per password: password_expiry_reminded_at
        // must be older than the last password change.
        User::query()
            ->whereNotNull('email_verified_at')
            ->whereNull('suspended_at')
            ->where('password_changed_at', '<=', now()->subDays($maxAge - $warning))
            ->where('password_changed_at', '>', now()->subDays($maxAge))
            ->where(fn ($query) => $query
                ->whereNull('password_expiry_reminded_at')
                ->orWhereColumn('password_expiry_reminded_at', '<', 'password_changed_at'))
            ->chunkById(200, function ($users) use (&$sent): void {
                foreach ($users as $user) {
                    /** @var User $user */
                    $user->notify(new PasswordExpiringNotification);

                    // Plain query update: no model events, no password aging side effects.
                    User::query()->whereKey($user->getKey())->update(['password_expiry_reminded_at' => now()]);

                    $sent++;
                }
            });

        return $sent;
    }

    public function remindSubscriptionRenewals(): int
    {
        $days = (int) config('security.renewal_warning_days');
        $sent = 0;

        Subscription::query()
            ->grantingProfessional()
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '>', now())
            ->where('current_period_end', '<=', now()->addDays($days))
            ->where(fn ($query) => $query
                ->whereNull('renewal_reminded_for')
                ->orWhereColumn('renewal_reminded_for', '!=', 'current_period_end'))
            ->whereHas('user', fn ($user) => $user->whereNotNull('email_verified_at')->whereNull('suspended_at'))
            ->with('user')
            ->chunkById(200, function ($subscriptions) use (&$sent): void {
                foreach ($subscriptions as $subscription) {
                    /** @var Subscription $subscription */
                    if (! $subscription->isRenewalDueSoon() || $subscription->user === null) {
                        continue;
                    }

                    $subscription->user->notify(new SubscriptionRenewalNotification($subscription));

                    Subscription::query()->whereKey($subscription->getKey())
                        ->update(['renewal_reminded_for' => $subscription->current_period_end]);

                    $sent++;
                }
            });

        return $sent;
    }
}
