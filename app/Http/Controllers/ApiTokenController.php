<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\ApiTokenData;
use App\Enums\ApiTokenAbility;
use App\Exceptions\PlanLimitException;
use App\Http\Requests\StoreApiTokenRequest;
use App\Interfaces\Services\ApiTokenServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    public function __construct(protected ApiTokenServiceInterface $tokens) {}

    public function index(): View
    {
        $user = Auth::user();

        return view('settings.tokens', [
            'tokens' => $this->tokens->list($user),
            'canCreate' => $user->hasFeature('api_access'),
            'abilityGroups' => collect(ApiTokenAbility::cases())->groupBy(fn (ApiTokenAbility $a): string => $a->resource()),
            'abilityLabels' => ApiTokenAbility::options(),
            'showDocsLink' => app()->isLocal() || $user->isAdmin(),
        ]);
    }

    public function store(StoreApiTokenRequest $request): RedirectResponse
    {
        try {
            $plain = $this->tokens->create(Auth::user(), ApiTokenData::fromArray($request->validated()));
        } catch (PlanLimitException $e) {
            return redirect()->route('settings.tokens.index')->with('error', $e->getMessage());
        }

        // PRG: the plain token is flashed once, so a reload never re-creates
        // a token nor shows it again.
        return redirect()->route('settings.tokens.index')
            ->with('plain_token', $plain)
            ->with('success', 'Token criado! Copie-o agora: ele não será exibido novamente.');
    }

    public function destroy(string $tokenId): RedirectResponse
    {
        abort_unless($this->tokens->revoke(Auth::user(), $tokenId), 404);

        return redirect()->route('settings.tokens.index')
            ->with('success', 'Token revogado. Aplicações que o usavam perderam o acesso.');
    }
}
