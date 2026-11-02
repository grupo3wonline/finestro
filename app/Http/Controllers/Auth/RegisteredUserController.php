<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Interfaces\Services\CaptchaServiceInterface;
use App\Models\User;
use App\Models\WalletMember;
use App\Rules\Honeypot;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * When arriving from a wallet invitation link (?invite=token), prefill the
     * email so the account matches the invited address and claims the invite.
     */
    public function create(Request $request): View
    {
        $invitedEmail = null;

        if ($token = $request->query('invite')) {
            $invitedEmail = WalletMember::query()
                ->whereNull('user_id')
                ->where('invitation_token', $token)
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->value('email');
        }

        return view('auth.register', ['invitedEmail' => $invitedEmail]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, CaptchaServiceInterface $captcha): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:'.User::class, 'alpha_dash'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['required', 'accepted'],
            // Anti-spam: campo isca (deve ficar vazio) + time-trap assinado.
            'website' => ['nullable', new Honeypot('website')],
            // Anti-bot: reCAPTCHA v3 e/ou Turnstile (somente se configurados no .env).
            ...$captcha->rules('register'),
        ], [
            'terms.required' => 'Você deve aceitar os Termos de Uso para criar sua conta.',
            'terms.accepted' => 'Você deve aceitar os Termos de Uso para criar sua conta.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'terms_accepted_at' => now(),
            'terms_version' => config('legal.policy_version'),
        ]);

        // Every new account gets the default ACL role.
        $user->assignRole('user');

        // Sends the confirmation e-mail (queued). Wallet invitations are claimed
        // only after verification, by the ClaimWalletInvitations listener on the
        // Verified event.
        event(new Registered($user));

        // The account is only active after the e-mail is confirmed, so there is
        // no session yet: the user signs in after clicking the link.
        return redirect()->route('login')->with(
            'status',
            'Conta criada! Enviamos um link de confirmação para '.$user->email.'. Sua conta só será ativada depois que você confirmar o e-mail (veja também a caixa de spam).'
        );
    }
}
