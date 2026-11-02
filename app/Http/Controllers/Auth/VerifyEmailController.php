<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * Confirm the e-mail address behind a signed link.
     *
     * Works without a session: a new account cannot sign in before confirming,
     * so the link is opened from the inbox by an anonymous visitor. The route
     * is signed and the hash must match the account e-mail, which is what
     * proves ownership of the mailbox.
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::query()->find($id);

        if (! $user instanceof User || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403);
        }

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Someone already signed in as this very account goes straight in.
        if ($request->user()?->is($user)) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        return redirect()->route('login')
            ->with('status', 'E-mail confirmado! Sua conta está ativa. Entre com seus dados para começar.');
    }
}
