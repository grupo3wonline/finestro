<?php

namespace App\Http\Middleware;

use App\Interfaces\Services\PlanPricingInterface;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimit
{
    public function __construct(
        protected PlanPricingInterface $pricing,
    ) {}

    /**
     * Handle an incoming request.
     * Usage: middleware('plan.limit:wallet') or middleware('plan.limit:feature,simulators')
     */
    public function handle(Request $request, Closure $next, string $type, ?string $resource = null): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($type === 'feature') {
            if (! $user->hasFeature($resource)) {
                return redirect()->back()->with('error', "Este recurso está disponível no plano Profissional por {$this->pricing->formattedCurrent()}/mês.{$this->promoSuffix()}");
            }
        }

        if ($type === 'create') {
            if (! $user->canCreate($resource)) {
                return redirect()->back()->with('error', "Você atingiu o limite do plano gratuito para {$this->getResourceLabel($resource)}. Faça upgrade para o plano Profissional por {$this->pricing->formattedCurrent()}/mês.{$this->promoSuffix()}");
            }
        }

        return $next($request);
    }

    /**
     * Promo end sentence, only when a real promo is running.
     */
    private function promoSuffix(): string
    {
        $endsAt = $this->pricing->promoEndsAt();

        return $endsAt instanceof CarbonInterface ? " Oferta válida até {$endsAt->format('d/m/Y')}." : '';
    }

    private function getResourceLabel(string $resource): string
    {
        return match ($resource) {
            'wallet' => 'carteiras',
            'goal' => 'metas',
            'transaction' => 'transações deste mês',
            'category' => 'categorias',
            'budget' => 'orçamentos deste mês',
            default => $resource,
        };
    }
}
