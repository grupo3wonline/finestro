<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRUD básico de metas financeiras e proteção contra acesso cruzado entre
 * usuários (qualquer registro estrangeiro responde 404).
 */
class GoalCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_user_can_create_goal(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('goals.store'), [
                'name' => 'Viagem',
                'target_amount' => '5000',
                'deadline' => now()->addYear()->toDateString(),
            ]);

        $response->assertRedirect(route('goals.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('goals', [
            'user_id' => $this->user->id,
            'name' => 'Viagem',
        ]);
    }

    public function test_user_can_update_goal(): void
    {
        $goal = Goal::factory()->for($this->user)->create(['target_amount' => 1000]);

        $response = $this->actingAs($this->user)
            ->put(route('goals.update', $goal), [
                'name' => $goal->name,
                'target_amount' => '7500',
                'deadline' => now()->addYear()->toDateString(),
            ]);

        $response->assertRedirect(route('goals.index'));
        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'target_amount' => '7500.00',
        ]);
    }

    public function test_user_can_delete_goal(): void
    {
        $goal = Goal::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->delete(route('goals.destroy', $goal));

        $response->assertRedirect(route('goals.index'));
        $this->assertSoftDeleted('goals', ['id' => $goal->id]);
    }

    public function test_intruder_cannot_update_or_delete_other_users_goal(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->for($owner)->create(['name' => 'Meta Secreta', 'target_amount' => 1000]);

        $this->actingAs($intruder)
            ->put(route('goals.update', $goal), [
                'name' => 'Invadida',
                'target_amount' => '1',
            ])
            ->assertNotFound();

        $this->actingAs($intruder)
            ->delete(route('goals.destroy', $goal))
            ->assertNotFound();

        // Certifica-se de que a meta não foi alterada.
        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'name' => 'Meta Secreta',
            'deleted_at' => null,
        ]);
    }
}
