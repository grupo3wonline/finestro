<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Factories\SubscriptionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests para calculadoras e simuladores: verifica que todas as rotas
 * retornam HTTP 200 sem erros de renderização de Blade.
 */
class CalculatorSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function proUser(): User
    {
        $user = User::factory()->create();
        SubscriptionFactory::new()->for($user)->create(['current_period_end' => now()->addMonth()]);

        return $user->fresh();
    }

    public function test_all_calculator_pages_return_200(): void
    {
        $user = User::factory()->create();

        $calculatorRoutes = [
            'calculators.index',
            'calculators.investment',
            'calculators.interest',
            'calculators.retirement',
            'calculators.thirteenth-salary',
            'calculators.vacation',
            'calculators.financing',
            'calculators.currency',
        ];

        foreach ($calculatorRoutes as $routeName) {
            $this->actingAs($user)
                ->get(route($routeName))
                ->assertOk();
        }
    }

    public function test_all_simulator_pages_return_200(): void
    {
        // Apenas usuários Profissionais têm acesso pleno aos simuladores
        // (usuários gratuitos veem a tela "bloqueada", mas ainda retorna 200).
        $user = $this->proUser();

        $simulatorRoutes = [
            'simulators.index',
            'simulators.investment',
            'simulators.retirement',
            'simulators.budget',
            'simulators.financial-profile',
            'simulators.investor-profile',
        ];

        foreach ($simulatorRoutes as $routeName) {
            $this->actingAs($user)
                ->get(route($routeName))
                ->assertOk();
        }
    }
}
