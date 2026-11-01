<?php

namespace App\Services;

use App\Interfaces\Services\SecurityActivityLoggerInterface;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SecurityActivityLogger implements SecurityActivityLoggerInterface
{
    public const LOG_NAME = 'security';

    public const EVENT_LOGIN_FAILED = 'login_failed';

    public function log(string $event, ?User $subject, array $properties = [], ?User $causer = null): void
    {
        $logger = activity(self::LOG_NAME)
            ->event($event)
            ->withProperties($properties + ['ip' => request()->ip()]);

        if ($subject instanceof User) {
            $logger->performedOn($subject);
        }

        if ($event === self::EVENT_LOGIN_FAILED) {
            $logger->causedByAnonymous();
        } else {
            $logger->causedBy($causer ?? Auth::user() ?? $subject);
        }

        $logger->log($event);
    }
}
