<?php

declare(strict_types=1);

namespace App\Rules;

use App\Interfaces\Services\CaptchaServiceInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Cloudflare Turnstile token check for the field filled by the
 * <x-ui.captcha /> component. Implicit: it also runs when the token is
 * missing, so a request that skips the widget is rejected.
 */
class TurnstileToken implements ValidationRule
{
    public bool $implicit = true;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $token = is_string($value) ? $value : '';

        if (! app(CaptchaServiceInterface::class)->verifyTurnstile($token, request()->ip())) {
            $fail('A verificação de segurança não foi concluída. Aguarde o selo ficar verde e tente novamente.');
        }
    }
}
