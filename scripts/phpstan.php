<?php

declare(strict_types=1);

/**
 * PHPStan wrapper — Windows junction-point fix.
 *
 * Problem: when the project lives behind a Windows junction point (e.g.
 * homol/Finestro → sys/homol/Finestro), PHP's realpath() resolves the cwd
 * to the junction TARGET before the phar is opened.  PHPStan then registers
 * the phar under the target path ("sys/homol/…/phpstan.phar") but later tries
 * to access its internal stubs with a phar:// URI derived from the same target
 * path.  On some PHP/Windows builds this round-trip breaks phar path resolution
 * and throws "standard_10.stub is not a file" for every file analysed.
 *
 * Fix: chdir() to realpath(cwd) BEFORE the phar is opened so both the phar
 * registration and all subsequent internal path lookups use the same canonical
 * (real) path.  On non-junction environments realpath() === getcwd() and this
 * wrapper is a no-op.
 *
 * Usage (composer.json):
 *   "@php scripts/phpstan.php analyse --memory-limit=1G --no-progress"
 */

$realDir = realpath(getcwd());

if ($realDir !== false && $realDir !== getcwd()) {
    // Junction detected: switch to the real path so the phar opens cleanly.
    chdir($realDir);
}

$phar = implode(DIRECTORY_SEPARATOR, [
    $realDir ?: getcwd(),
    'vendor', 'phpstan', 'phpstan', 'phpstan.phar',
]);

if (! is_file($phar)) {
    fwrite(STDERR, "PHPStan phar not found at: {$phar}\n");
    fwrite(STDERR, "Run 'composer install' first.\n");
    exit(1);
}

// Forward all arguments (minus $argv[0]) to phpstan.
$extraArgs = implode(' ', array_map('escapeshellarg', array_slice($argv, 1)));

passthru(PHP_BINARY . ' ' . escapeshellarg($phar) . ' ' . $extraArgs, $exitCode);
exit($exitCode);
