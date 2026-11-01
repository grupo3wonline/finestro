<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Display helper for Stripe object ids: keeps the prefix and the last four
 * characters only (e.g. "sub_…1a2b"), so full ids never reach the admin UI.
 */
final class StripeId
{
    public static function short(?string $id): string
    {
        if ($id === null || $id === '') {
            return '—';
        }

        $prefix = str_contains($id, '_') ? strstr($id, '_', true).'_' : '';

        return $prefix.'…'.substr($id, -4);
    }
}
