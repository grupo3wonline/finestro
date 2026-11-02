<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Interfaces\Services\SecurityActivityLoggerInterface;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Forced password change for accounts whose password is older than the
 * configured maximum age (90 days by default).
 */
class ExpiredPasswordController extends Controller
{
    public function show(Request $request): RedirectResponse|View
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isPasswordExpired()) {
            return redirect()->route('dashboard');
        }

        return view('auth.password-expired', ['user' => $user]);
    }

    public function update(Request $request, SecurityActivityLoggerInterface $security): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ], [
            'current_password.required' => 'Informe a senha atual.',
            'current_password.current_password' => 'A senha atual está incorreta.',
            'password.required' => 'Informe a nova senha.',
            'password.confirmed' => 'A confirmação da nova senha não confere.',
            'password.different' => 'A nova senha deve ser diferente da senha atual.',
        ]);

        // The User model restarts the password aging clock on save.
        $user->update(['password' => Hash::make((string) $request->input('password'))]);

        $security->log('password_changed', $user);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false))
            ->with('success', 'Senha alterada com sucesso! Ela será válida pelos próximos '.config('security.password_max_age_days').' dias.');
    }
}
