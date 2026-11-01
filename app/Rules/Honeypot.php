<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Anti-spam honeypot validation.
 *
 * Works together with the <x-ui.honeypot /> Blade component, which renders:
 *   - a decoy text field (default name "website") that must stay empty; bots
 *     that auto-fill every field will trip it;
 *   - a signed timestamp field ("<name>_time") used as a time-trap: a real
 *     person takes at least a couple of seconds to fill a form, while bots
 *     submit almost instantly.
 *
 * Attach the rule to the decoy field:
 *   'website' => [new Honeypot('website')]
 */
class Honeypot implements ValidationRule
{
    /** Minimum seconds a genuine human is expected to take. */
    private const int MIN_SECONDS = 2;

    /** Maximum age of the form before the token is considered stale (1h). */
    private const int MAX_SECONDS = 3600;

    public function __construct(private string $field = 'website') {}

    /**
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 1) The decoy must be empty. Any content means a bot filled it.
        if (! empty($value)) {
            $fail('Falha na verificação anti-spam.')->translate();

            return;
        }

        // 2) Time-trap: decode the signed timestamp and check elapsed time.
        $timeField = $this->field.'_time';
        $encrypted = request()->input($timeField);

        if (! is_string($encrypted) || $encrypted === '') {
            $fail('Falha na verificação anti-spam.');

            return;
        }

        try {
            $submittedAt = (int) Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            // Tampered or invalid token.
            $fail('Falha na verificação anti-spam.');

            return;
        }

        $elapsed = now()->timestamp - $submittedAt;

        if ($elapsed < self::MIN_SECONDS || $elapsed > self::MAX_SECONDS) {
            $fail('Formulário enviado rápido demais ou expirado. Tente novamente.');
        }
    }
}
