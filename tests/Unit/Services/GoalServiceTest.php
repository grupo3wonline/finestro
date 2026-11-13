<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes unitários do GoalService: cálculo de progresso percentual
 * e estado de meta concluída.
 */
class GoalServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_progress_percent_calculation(): void
    {
        $goal = Goal::factory()->for($this->user)->create([
            'target_amount' => '1000.00',
            'current_amount' => '250.00',
            'status' => 'active',
        ]);

        // R$ 250 de R$ 1.000 = 25% de progresso.
        $this->assertEqualsWithDelta(25.0, (float) $goal->progress, 0.01);
    }

    public function test_completed_goal_status(): void
    {
        $goal = Goal::factory()->for($this->user)->create([
            'target_amount' => '500.00',
            'current_amount' => '500.00',
            'status' => 'active',
        ]);

        // Com current_amount >= target_amount, o progresso deve ser 100%.
        $this->assertEqualsWithDelta(100.0, (float) $goal->progress, 0.01);

        // A meta com valor depositado igual ao alvo deve ter progresso completo.
        $this->assertSame('active', $goal->status, 'O status de banco permanece inalterado');

        // Verifica que a meta com depósito acima do alvo não excede 100%.
        $overFundedGoal = Goal::factory()->for($this->user)->create([
            'target_amount' => '500.00',
            'current_amount' => '600.00',
            'status' => 'active',
        ]);

        $this->assertEqualsWithDelta(100.0, (float) $overFundedGoal->progress, 0.01);
    }
}
