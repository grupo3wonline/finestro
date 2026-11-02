<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Bot protection for public forms: Google reCAPTCHA v3 and Cloudflare Turnstile.
 */
interface CaptchaServiceInterface
{
    /** Request field filled by the reCAPTCHA v3 script. */
    public const string RECAPTCHA_FIELD = 'g-recaptcha-response';

    /** Request field filled by the Turnstile widget. */
    public const string TURNSTILE_FIELD = 'cf-turnstile-response';

    public function recaptchaEnabled(): bool;

    public function turnstileEnabled(): bool;

    /** True when at least one provider is configured. */
    public function enabled(): bool;

    /**
     * Validation rules for the enabled providers, keyed by request field.
     * Empty when no provider is configured.
     *
     * @return array<string, list<ValidationRule>>
     */
    public function rules(string $action): array;

    /**
     * Verify a reCAPTCHA v3 token for the given action. Fails closed.
     */
    public function verifyRecaptcha(string $token, string $action, ?string $ip = null): bool;

    /**
     * Verify a Turnstile token. Fails closed.
     */
    public function verifyTurnstile(string $token, ?string $ip = null): bool;
}
