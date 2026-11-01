<?php

namespace Database\Seeders;

use App\DTOs\TransactionDTO;
use App\Interfaces\Services\TransactionServiceInterface;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ACL roles and permissions must exist before any user is created.
        $this->call(RolesAndPermissionsSeeder::class);

        // Create demo user
        $user = User::create([
            'name' => 'Usuário Demo',
            'username' => 'demo',
            'email' => 'demo@finestro.com.br',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'terms_accepted_at' => now(),
            'terms_version' => config('legal.policy_version'),
        ]);
        $user->assignRole(RolesAndPermissionsSeeder::ROLE_USER);

        // Create wallets for demo user
        $wallets = [
            Wallet::create([
                'user_id' => $user->id,
                'name' => 'Conta Corrente',
                'type' => 'checking',
                'balance' => 5420.50,
                'color' => '#3B82F6',
                'icon' => 'fa-university',
            ]),
            Wallet::create([
                'user_id' => $user->id,
                'name' => 'Poupança',
                'type' => 'savings',
                'balance' => 12800.00,
                'color' => '#10B981',
                'icon' => 'fa-piggy-bank',
            ]),
            Wallet::create([
                'user_id' => $user->id,
                'name' => 'Cartão Nubank',
                'type' => 'credit',
                'balance' => 0,
                'credit_limit' => 6000,
                'closing_day' => 5,
                'due_day' => 12,
                'color' => '#8B5CF6',
                'icon' => 'fa-credit-card',
            ]),
        ];

        // Create categories
        $incomeCategories = [
            Category::create(['user_id' => $user->id, 'name' => 'Salário', 'type' => 'income', 'icon' => 'fa-briefcase', 'color' => '#10B981']),
            Category::create(['user_id' => $user->id, 'name' => 'Freelance', 'type' => 'income', 'icon' => 'fa-laptop', 'color' => '#3B82F6']),
        ];

        $expenseCategories = [
            Category::create(['user_id' => $user->id, 'name' => 'Alimentação', 'type' => 'expense', 'icon' => 'fa-utensils', 'color' => '#F59E0B']),
            Category::create(['user_id' => $user->id, 'name' => 'Transporte', 'type' => 'expense', 'icon' => 'fa-car', 'color' => '#EF4444']),
            Category::create(['user_id' => $user->id, 'name' => 'Moradia', 'type' => 'expense', 'icon' => 'fa-home', 'color' => '#8B5CF6']),
            Category::create(['user_id' => $user->id, 'name' => 'Lazer', 'type' => 'expense', 'icon' => 'fa-gamepad', 'color' => '#06B6D4']),
        ];

        // Create transactions
        $transactions = [
            ['wallet_id' => $wallets[0]->id, 'category_id' => $incomeCategories[0]->id, 'type' => 'income', 'amount' => 8500, 'description' => 'Salário mensal', 'date' => now()->startOfMonth()],
            ['wallet_id' => $wallets[0]->id, 'category_id' => $expenseCategories[0]->id, 'type' => 'expense', 'amount' => 850, 'description' => 'Supermercado', 'date' => now()->subDays(5)],
            ['wallet_id' => $wallets[0]->id, 'category_id' => $expenseCategories[1]->id, 'type' => 'expense', 'amount' => 350, 'description' => 'Combustível', 'date' => now()->subDays(3)],
            ['wallet_id' => $wallets[0]->id, 'category_id' => $expenseCategories[2]->id, 'type' => 'expense', 'amount' => 1800, 'description' => 'Aluguel', 'date' => now()->startOfMonth()->addDays(4)],
            ['wallet_id' => $wallets[0]->id, 'category_id' => $expenseCategories[3]->id, 'type' => 'expense', 'amount' => 120, 'description' => 'Cinema e jantar', 'date' => now()->subDays(2)],
            ['wallet_id' => $wallets[0]->id, 'category_id' => $incomeCategories[1]->id, 'type' => 'income', 'amount' => 2500, 'description' => 'Projeto freelance', 'date' => now()->subDays(7)],
        ];

        foreach ($transactions as $t) {
            Transaction::create(array_merge($t, ['user_id' => $user->id]));
        }

        // Create goals
        Goal::create([
            'user_id' => $user->id,
            'name' => 'Reserva de Emergência',
            'description' => '6 meses de despesas guardados',
            'target_amount' => 30000,
            'current_amount' => 12800,
            'deadline' => now()->addMonths(12),
            'status' => 'active',
            'icon' => 'fa-shield-alt',
            'color' => '#10B981',
        ]);

        Goal::create([
            'user_id' => $user->id,
            'name' => 'Viagem Europa',
            'description' => 'Férias de 15 dias na Europa',
            'target_amount' => 20000,
            'current_amount' => 5500,
            'deadline' => now()->addMonths(18),
            'status' => 'active',
            'icon' => 'fa-plane',
            'color' => '#3B82F6',
        ]);

        Goal::create([
            'user_id' => $user->id,
            'name' => 'Curso de Especialização',
            'description' => 'MBA em Gestão Financeira',
            'target_amount' => 15000,
            'current_amount' => 8000,
            'deadline' => now()->addMonths(6),
            'status' => 'active',
            'icon' => 'fa-graduation-cap',
            'color' => '#8B5CF6',
        ]);

        $this->seedNewFeatures($user, $wallets, $incomeCategories, $expenseCategories);

        // Conteúdo público da Central de Ajuda (independente do usuário demo).
        $this->call(FaqSeeder::class);
    }

    /**
     * Dados de demonstração dos recursos mesclados dos sistemas legados:
     * contas a pagar/receber, parcelamento no cartão, recorrência e orçamentos.
     * Usa o TransactionService para respeitar as regras de saldo e histórico.
     *
     * @param  list<Wallet>  $wallets
     * @param  list<Category>  $incomeCategories
     * @param  list<Category>  $expenseCategories
     */
    private function seedNewFeatures(User $user, array $wallets, array $incomeCategories, array $expenseCategories): void
    {
        /** @var TransactionServiceInterface $service */
        $service = app(TransactionServiceInterface::class);
        [$checking, , $card] = $wallets;
        [$food, $transport, $housing, $leisure] = $expenseCategories;

        $make = fn (array $data) => $service->createTransaction(TransactionDTO::fromArray(array_merge(['user_id' => $user->id], $data)));

        // Contas a pagar (uma atrasada, uma para os próximos dias, um boleto futuro)
        $make(['wallet_id' => $checking->id, 'category_id' => $housing->id, 'type' => 'expense', 'status' => 'pending',
            'amount' => 289.90, 'description' => 'Conta de luz', 'date' => now()->subDays(3)->toDateString(),
            'due_date' => now()->subDays(1)->toDateString(), 'payment_method' => 'boleto']);
        $make(['wallet_id' => $checking->id, 'category_id' => $housing->id, 'type' => 'expense', 'status' => 'pending',
            'amount' => 129.90, 'description' => 'Internet fibra', 'date' => now()->toDateString(),
            'due_date' => now()->addDays(4)->toDateString(), 'payment_method' => 'auto_debit',
            'is_recurring' => true, 'recurring_frequency' => 'monthly']);

        // Conta a receber
        $make(['wallet_id' => $checking->id, 'category_id' => $incomeCategories[1]->id, 'type' => 'income', 'status' => 'pending',
            'amount' => 1800, 'description' => 'Projeto site institucional (2ª parte)', 'date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(), 'payment_method' => 'pix']);

        // Compra parcelada no cartão: R$ 2.399,00 em 10x (consome o limite na hora)
        $make(['wallet_id' => $card->id, 'category_id' => $leisure->id, 'type' => 'expense', 'status' => 'paid',
            'amount' => 2399.00, 'description' => 'Notebook', 'date' => now()->subDays(2)->toDateString(),
            'payment_method' => 'credit_card', 'installments' => 10]);

        // Compras à vista no cartão
        $make(['wallet_id' => $card->id, 'category_id' => $food->id, 'type' => 'expense', 'status' => 'paid',
            'amount' => 187.45, 'description' => 'Restaurante', 'date' => now()->subDay()->toDateString(), 'payment_method' => 'credit_card']);
        $make(['wallet_id' => $card->id, 'category_id' => $transport->id, 'type' => 'expense', 'status' => 'paid',
            'amount' => 64.30, 'description' => 'Aplicativo de transporte', 'date' => now()->toDateString(), 'payment_method' => 'credit_card']);

        // Orçamentos do mês
        Budget::create(['user_id' => $user->id, 'category_id' => null, 'year' => now()->year, 'month' => now()->month, 'amount' => 5000, 'alert_threshold' => 80]);
        Budget::create(['user_id' => $user->id, 'category_id' => $food->id, 'year' => now()->year, 'month' => now()->month, 'amount' => 1000, 'alert_threshold' => 80]);
        Budget::create(['user_id' => $user->id, 'category_id' => $leisure->id, 'year' => now()->year, 'month' => now()->month, 'amount' => 300, 'alert_threshold' => 80]);
    }
}
