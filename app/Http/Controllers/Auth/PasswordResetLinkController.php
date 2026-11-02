<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Interfaces\Services\CaptchaServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, CaptchaServiceInterface $captcha): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            // Anti-bot: reCAPTCHA v3 e/ou Turnstile (somente se configurados no .env).
            ...$captcha->rules('forgot_password'),
        ]);

        // The broker result (sent, unknown user or throttled) is deliberately
        // not shown: answering every case with the same neutral message stops
        // anyone from checking whether an e-mail has an account. The route
        // throttle (6/min per IP) still limits repeated requests.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', __('passwords.sent'));
    }
}
