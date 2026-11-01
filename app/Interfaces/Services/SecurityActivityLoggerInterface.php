<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\Models\User;

interface SecurityActivityLoggerInterface
{
    /**
     * Record an authentication or security event in the `security` log.
     *
     * The causer is $causer when given (e.g. the admin acting on another
     * account), otherwise the authenticated user, falling back to $subject;
     * for `login_failed` the causer is always null (the actor is unknown).
     * Only the request IP is added; never pass passwords, tokens or credentials.
     *
     * @param  array<string, mixed>  $properties
     */
    public function log(string $event, ?User $subject, array $properties = [], ?User $causer = null): void;
}
