<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * The account could not be deleted (e.g. the Stripe subscription could not
 * be canceled first). The message is user-facing (pt-BR) and shown as a flash error.
 */
class AccountDeletionException extends \RuntimeException {}
