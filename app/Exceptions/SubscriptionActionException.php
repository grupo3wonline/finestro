<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * A plan management action is not allowed in the current subscription state.
 * The message is user-facing (pt-BR) and shown as a flash error.
 */
class SubscriptionActionException extends \RuntimeException {}
