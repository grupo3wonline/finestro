<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Factories\SubscriptionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Exportação de relatórios: plano gratuito é bloqueado, plano Profissional
 * tem acesso a CSV, PDF e envio por e-mail (com throttle).
 */
class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    /** Cria um usuário no plano Profissional com assinatura ativa. */
    private function professional(): User
    {
        $user = User::factory()->create();
        SubscriptionFactory::new()->for($user)->create(['current_period_end' => now()->addMonth()]);

        return $user->fresh();
    }

    /** Cria um usuário no plano Gratuito. */
    private function freeUser(): User
    {
        return User::factory()->create();
    }

    public function test_free_plan_cannot_export_csv(): void
    {
        $user = $this->freeUser();

        $response = $this->actingAs($user)
            ->get(route('reports.export', ['type' => 'expense', 'year' => now()->year]));

        // O controlador redireciona com mensagem de erro — não retorna 200.
        $response->assertRedirect(route('reports.index'));
        $response->assertSessionHas('error');
    }

    public function test_professional_can_export_csv(): void
    {
        $user = $this->professional();

        $response = $this->actingAs($user)
            ->get(route('reports.export', ['type' => 'expense', 'year' => now()->year]));

        $response->assertOk();
        $this->assertStringContainsStringIgnoringCase(
            'text/csv',
            (string) $response->headers->get('Content-Type')
        );
    }

    public function test_professional_can_generate_pdf(): void
    {
        $user = $this->professional();

        $response = $this->actingAs($user)
            ->get(route('reports.pdf', ['year' => now()->year]));

        $response->assertOk();
    }

    public function test_email_report_is_throttled(): void
    {
        Mail::fake();
        $this->freezeTime();

        $user = $this->professional();

        // A rota tem throttle:3,1 — três chamadas passam, a quarta retorna 429.
        foreach (range(1, 3) as $attempt) {
            $this->actingAs($user)
                ->post(route('reports.email'), ['year' => now()->year])
                ->assertRedirect();
        }

        $this->actingAs($user)
            ->post(route('reports.email'), ['year' => now()->year])
            ->assertStatus(429);
    }
}
