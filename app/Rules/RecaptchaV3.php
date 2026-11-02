<?php

declare(strict_types=1);

namespace App\Rules;

use App\Interfaces\Services\CaptchaServiceInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Google reCAPTCHA v3 token check for the field filled by the
 * <x-ui.captcha /> component. Implicit: it also runs when the token is
 * missing, so a request that skips the script is rejected.
 */
class RecaptchaV3 implements ValidationRule
{
    public bool $implicit = true;

    public function __construct(private readonly string $action) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $token = is_string($value) ? $value : '';

        if (! app(CaptchaServiceInterface::class)->verifyRecaptcha($token, $this->action, request()->ip())) {
            $fail('Não foi possível confirmar que você é uma pessoa. Atualize a página e tente novamente.');
        }
    }
}
