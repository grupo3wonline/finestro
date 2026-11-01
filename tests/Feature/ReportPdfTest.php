<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Database\Factories\SubscriptionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPdfTest extends TestCase
{
    use RefreshDatabase;

    private function professional(): User
    {
        $user = User::factory()->create(['terms_accepted_at' => now()]);
        SubscriptionFactory::new()->for($user)->create(['current_period_end' => now()->addMonth()]);

        return $user->fresh();
    }

    public function test_professional_user_downloads_pdf_report(): void
    {
        $user = $this->professional();
        $wallet = Wallet::factory()->for($user)->create(['type' => 'checking', 'balance' => 3000]);
        Transaction::factory()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'type' => 'expense',
            'status' => 'paid',
            'amount' => 150,
            'date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_free_user_is_blocked_from_pdf_report(): void
    {
        $user = User::factory()->create(['terms_accepted_at' => now()]);

        $response = $this->actingAs($user)->get(route('reports.pdf'));

        $response->assertRedirect(route('reports.index'));
        $response->assertSessionHas('error');
    }
}
