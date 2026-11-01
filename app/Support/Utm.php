<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Extracts whitelisted UTM parameters from the current request so sign-up
 * links can carry campaign attribution without echoing arbitrary input.
 */
final class Utm
{
    private const array KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

    private const string PATTERN = '/^[A-Za-z0-9._-]{1,100}$/';

    /**
     * @return array<string, string>
     */
    public static function fromRequest(Request $request): array
    {
        $utm = [];

        foreach (self::KEYS as $key) {
            $value = $request->query($key);

            if (is_string($value) && preg_match(self::PATTERN, $value) === 1) {
                $utm[$key] = $value;
            }
        }

        return $utm;
    }
}
