<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Interfaces\Services\CaptchaServiceInterface;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Public "send the confirmation e-mail again" form. Accounts that did not
 * confirm their e-mail cannot sign in, so this cannot sit behind auth.
 */
class ResendVerificationController extends Controller
{
    /** Confirmation e-mails per address per hour (stops mail-bombing a victim). */
    private const int MAX_PER_HOUR = 3;

    public function create(): View
    {
        return view('auth.resend-verification');
    }

    public function store(Request $request, CaptchaServiceInterface $captcha): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            ...$captcha->rules('resend_verification'),
        ]);

        $email = Str::lower((string) $request->input('email'));

        $user = User::query()->where('email', $email)->first();

        if ($user instanceof User && ! $user->hasVerifiedEmail() && ! $user->isSuspended()) {
            RateLimiter::attempt(
                'resend-verification:'.sha1($email),
                self::MAX_PER_HOUR,
                static fn () => $user->sendEmailVerificationNotification(),
                3600,
            );
        }

        // Same answer for unknown, already confirmed and throttled addresses,
        // so the form cannot be used to discover which e-mails have accounts.
        return back()->with('status', 'Se houver uma conta aguardando confirmação para este e-mail, enviamos um novo link. Confira também a caixa de spam.');
    }
}
