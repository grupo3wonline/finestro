<?php

namespace App\Http\Requests\Auth;

use App\Interfaces\Services\CaptchaServiceInterface;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Login requires the username, the e-mail and the password of the same
     * account.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            // Anti-bot: reCAPTCHA v3 e/ou Turnstile (somente se configurados no .env).
            ...app(CaptchaServiceInterface::class)->rules('login'),
        ];
    }

    /**
     * Custom attribute names for validation messages (pt-BR).
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'username' => 'usuário',
            'email' => 'e-mail',
            'password' => 'senha',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * The user provider matches username AND e-mail on the same row, so a
     * mismatch between them fails exactly like a wrong password. Every
     * failure uses the generic "login" error key.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        /** @var SessionGuard $guard */
        $guard = Auth::guard('web');

        // An account is only active once its e-mail is confirmed, and a
        // suspended account never signs in.
        $canSignIn = fn (User $user): bool => ! $user->isSuspended() && $user->hasVerifiedEmail();

        if (! $guard->attemptWhen($this->credentials(), $canSignIn, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            // The suspension / pending-confirmation notices are shown only with
            // the right password, so they never reveal whether an account exists.
            $lastAttempted = $guard->getLastAttempted();

            if ($lastAttempted instanceof User
                && $guard->getProvider()->validateCredentials($lastAttempted, ['password' => (string) $this->string('password')])) {
                if ($lastAttempted->isSuspended()) {
                    throw ValidationException::withMessages([
                        'login' => User::SUSPENDED_MESSAGE,
                    ]);
                }

                if (! $lastAttempted->hasVerifiedEmail()) {
                    throw ValidationException::withMessages([
                        'login' => User::UNVERIFIED_MESSAGE,
                    ]);
                }
            }

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Build the credential array: username + e-mail (lower-cased, as stored
     * at registration) + password.
     *
     * @return array<string, string>
     */
    protected function credentials(): array
    {
        return [
            'username' => (string) $this->string('username'),
            'email' => Str::lower((string) $this->string('email')),
            'password' => (string) $this->string('password'),
        ];
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower((string) $this->string('username')).'|'
            .Str::lower((string) $this->string('email')).'|'
            .$this->ip()
        );
    }
}
