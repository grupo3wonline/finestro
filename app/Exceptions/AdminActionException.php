<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * An admin panel action was refused (acting on yourself, removing the last
 * active admin, ...). The message is user-facing (pt-BR) and shown as a
 * danger notification.
 */
class AdminActionException extends \RuntimeException {}
