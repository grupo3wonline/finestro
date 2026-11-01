<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * A personal data export could not be requested (e.g. one is already being
 * prepared). The message is user-facing (pt-BR) and shown as a flash error.
 */
class DataExportException extends \RuntimeException {}
