<?php

namespace App\Http\Controllers;

use App\Exceptions\AccountDeletionException;
use App\Interfaces\Services\AccountActivityServiceInterface;
use App\Interfaces\Services\AccountDeletionServiceInterface;
use App\Interfaces\Services\SecurityActivityLoggerInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        return view('settings.index', ['user' => $user]);
    }

    public function activity(AccountActivityServiceInterface $activities): View
    {
        return view('settings.activity', [
            'entries' => $activities->paginateForUser(Auth::user()),
        ]);
    }

    public function updatePassword(Request $request, SecurityActivityLoggerInterface $security): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ], [
            'current_password.required' => 'A senha atual é obrigatória.',
            'current_password.current_password' => 'A senha atual está incorreta.',
            'password.required' => 'A nova senha é obrigatória.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'password.different' => 'A nova senha deve ser diferente da senha atual.',
        ]);

        $user = Auth::user();

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $security->log('password_changed', $user);

        return redirect()->route('settings.index')
            ->with('success', 'Senha alterada com sucesso!');
    }

    public function deleteAccount(Request $request, AccountDeletionServiceInterface $accounts): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'A senha é obrigatória para excluir a conta.',
            'password.current_password' => 'Senha incorreta.',
        ]);

        $user = Auth::user();

        try {
            $accounts->delete($user);
        } catch (AccountDeletionException $e) {
            return redirect()->route('settings.privacy')->with('error', $e->getMessage());
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('success', 'Sua conta foi excluída e seu acesso foi encerrado.');
    }
}
