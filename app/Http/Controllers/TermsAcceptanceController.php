<?php

namespace App\Http\Controllers;

use App\Interfaces\Services\LegalConsentServiceInterface;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Terms of Use / Privacy Policy acceptance gate (first acceptance and
 * re-acceptance after config('legal.policy_version') changes).
 */
class TermsAcceptanceController extends Controller
{
    public function __construct(
        private readonly LegalConsentServiceInterface $legalConsent,
    ) {}

    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('auth.accept-terms', [
            'previouslyAccepted' => $user->terms_accepted_at !== null,
            'policyVersion' => $this->legalConsent->currentVersion(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['terms' => ['required', 'accepted']], [
            'terms.required' => 'Você deve aceitar os Termos de Uso.',
            'terms.accepted' => 'Você deve aceitar os Termos de Uso.',
        ]);

        /** @var User $user */
        $user = $request->user();
        $this->legalConsent->acceptTerms($user);

        return redirect()->route('dashboard')->with('success', 'Termos aceitos com sucesso! Bem-vindo ao Finestro.');
    }
}
