<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * The user's plan does not allow the requested action. The message is
 * user-facing (pt-BR); errorCode() is the machine-readable API code.
 */
class PlanLimitException extends \RuntimeException
{
    public function __construct(string $message, private readonly string $errorCode = 'plan_limit_reached')
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
