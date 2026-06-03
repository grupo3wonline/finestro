<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimit
{
    /**
     * Handle an incoming request.
     * Usage: middleware('plan.limit:wallet') or middleware('plan.limit:feature,simulators')
     */
    public function handle(Request $request, Closure $next, string $type, ?string $resource = null): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($type === 'feature') {
            if (!$user->hasFeature($resource)) {
                return redirect()->back()->with('error', 'Este recurso está disponível apenas no plano Profissional. Faça upgrade para desbloquear.');
            }
        }

        if ($type === 'create') {
            if (!$user->canCreate($resource)) {
                return redirect()->back()->with('error', "Você atingiu o limite do plano gratuito para {$this->getResourceLabel($resource)}. Faça upgrade para o plano Profissional.");
            }
        }

        return $next($request);
    }

    private function getResourceLabel(string $resource): string
    {
        return match ($resource) {
            'wallet' => 'carteiras',
            'goal' => 'metas',
            'transaction' => 'transações deste mês',
            'category' => 'categorias',
            default => $resource,
        };
    }
}
