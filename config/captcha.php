<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bot protection (Google reCAPTCHA v3 + Cloudflare Turnstile)
|--------------------------------------------------------------------------
|
| Each provider is enabled only when BOTH its site key and its secret key are
| set. When both providers are enabled, a form must pass both checks. With no
| keys the protection is off (local development and the test suite); the
| honeypot rule keeps running regardless.
|
| Used by App\Services\CaptchaService and the <x-ui.captcha /> component on
| the guest forms: register, login, forgot/reset password and the
| "resend confirmation e-mail" form.
|
*/

return [

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        // reCAPTCHA v3 returns a score from 0.0 (bot) to 1.0 (human).
        'min_score' => (float) env('RECAPTCHA_MIN_SCORE', 0.5),
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    // Seconds to wait for the provider's siteverify endpoint. On timeout or
    // error the check fails closed (the form is rejected).
    'timeout' => (int) env('CAPTCHA_TIMEOUT', 5),

];
