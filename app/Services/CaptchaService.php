<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\Services\CaptchaServiceInterface;
use App\Rules\RecaptchaV3;
use App\Rules\TurnstileToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CaptchaService implements CaptchaServiceInterface
{
    private const string RECAPTCHA_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    private const string TURNSTILE_VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function recaptchaEnabled(): bool
    {
        return filled(config('captcha.recaptcha.site_key')) && filled(config('captcha.recaptcha.secret_key'));
    }

    public function turnstileEnabled(): bool
    {
        return filled(config('captcha.turnstile.site_key')) && filled(config('captcha.turnstile.secret_key'));
    }

    public function enabled(): bool
    {
        return $this->recaptchaEnabled() || $this->turnstileEnabled();
    }

    public function rules(string $action): array
    {
        $rules = [];

        if ($this->recaptchaEnabled()) {
            $rules[self::RECAPTCHA_FIELD] = [new RecaptchaV3($action)];
        }

        if ($this->turnstileEnabled()) {
            $rules[self::TURNSTILE_FIELD] = [new TurnstileToken];
        }

        return $rules;
    }

    public function verifyRecaptcha(string $token, string $action, ?string $ip = null): bool
    {
        $payload = $this->siteVerify(self::RECAPTCHA_VERIFY_URL, (string) config('captcha.recaptcha.secret_key'), $token, $ip);

        if ($payload === null || ($payload['success'] ?? false) !== true) {
            return false;
        }

        // The token must have been minted for this very form (action) and
        // score as a human. A missing score is treated as a bot.
        return ($payload['action'] ?? null) === $action
            && (float) ($payload['score'] ?? 0.0) >= (float) config('captcha.recaptcha.min_score');
    }

    public function verifyTurnstile(string $token, ?string $ip = null): bool
    {
        $payload = $this->siteVerify(self::TURNSTILE_VERIFY_URL, (string) config('captcha.turnstile.secret_key'), $token, $ip);

        return $payload !== null && ($payload['success'] ?? false) === true;
    }

    /**
     * POST the token to the provider. Returns null on any transport or
     * protocol error so the caller rejects the submission.
     *
     * @return array<string, mixed>|null
     */
    private function siteVerify(string $url, string $secret, string $token, ?string $ip): ?array
    {
        if ($token === '') {
            return null;
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('captcha.timeout'))
                ->post($url, array_filter([
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ]));

            if (! $response->successful()) {
                Log::warning('Captcha provider answered with an error status.', ['url' => $url, 'status' => $response->status()]);

                return null;
            }

            /** @var array<string, mixed>|null $json */
            $json = $response->json();

            return is_array($json) ? $json : null;
        } catch (Throwable $e) {
            // Never log the token or the secret.
            Log::warning('Captcha provider is unreachable.', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
