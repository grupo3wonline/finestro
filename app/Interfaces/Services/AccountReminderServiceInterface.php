<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

/**
 * E-mail reminders driven by the scheduler: password about to expire and
 * Professional subscription about to renew.
 */
interface AccountReminderServiceInterface
{
    /**
     * Send the "password expires soon" e-mail to every verified, active user
     * whose password enters the warning window and was not reminded yet.
     *
     * @return int Number of reminders queued.
     */
    public function remindPasswordExpiry(): int;

    /**
     * Send the "subscription renews soon" e-mail once per billing period.
     *
     * @return int Number of reminders queued.
     */
    public function remindSubscriptionRenewals(): int;
}
