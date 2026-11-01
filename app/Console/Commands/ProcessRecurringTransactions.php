<?php

namespace App\Console\Commands;

use App\DTOs\TransactionDTO;
use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Interfaces\Services\TransactionServiceInterface;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Gera as próximas ocorrências das transações recorrentes.
 *
 * Melhorias em relação à versão anterior:
 *  1. Trabalha por SÉRIE (recurring_source_id): só a ocorrência mais recente
 *     de cada série gera a próxima. Antes, cada ocorrência gerada também era
 *     "recorrente" e o comando partia de todas elas — se o usuário editasse o
 *     valor de uma, a checagem de duplicidade (que comparava o valor) falhava.
 *  2. Recupera atrasos: se o agendador ficou parado, gera TODAS as datas
 *     vencidas, não apenas uma por execução.
 *  3. Cria com antecedência (padrão: 7 dias) como PENDENTE, para a conta
 *     aparecer em "Contas a pagar/receber" antes de vencer. Débito automático
 *     e cartão de crédito são lançados como pagos na própria data (não antes).
 *  4. Usa o TransactionService: saldo só muda quando pago + histórico gravado.
 *  5. Parar uma recorrência = desmarcar "recorrente" na última ocorrência.
 */
class ProcessRecurringTransactions extends Command
{
    protected $signature = 'transactions:process-recurring
                            {--days-ahead=7 : Quantos dias de antecedência para gerar contas pendentes}
                            {--dry-run : Apenas mostra o que seria gerado}';

    protected $description = 'Gera as próximas ocorrências das transações recorrentes';

    /** Proteção contra loop infinito (ex.: diária parada há anos). */
    private const int MAX_PER_SERIES = 400;

    /** @var array<string, array<string, int>> user id => 'Y-m' => transações no mês */
    private array $monthlyCounts = [];

    public function handle(TransactionServiceInterface $transactionService): int
    {
        $today = Carbon::today();
        $horizon = $today->copy()->addDays((int) $this->option('days-ahead'));
        $dryRun = (bool) $this->option('dry-run');
        $created = 0;
        $skipped = 0;
        $this->monthlyCounts = [];

        // Última ocorrência de cada série (sem escopo de usuário: roda no console).
        $latestPerSeries = Transaction::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->with('user.subscription')
            ->whereNotNull('recurring_frequency')
            ->where(fn ($q) => $q->where('is_recurring', true)->orWhereNotNull('recurring_source_id'))
            ->orderBy('date')
            ->get()
            ->groupBy(fn (Transaction $t) => $t->recurring_source_id ?? $t->id)
            ->map(fn ($series): array => [
                'anchor' => $series->sortBy('date')->first()->date,
                'last' => $series->sortBy('date')->last(),
            ]);

        foreach ($latestPerSeries as $seriesId => ['anchor' => $anchor, 'last' => $last]) {
            // Série encerrada (última ocorrência desmarcada como recorrente)
            // ou sem dono válido (FK órfã, usuário removido): ignora a série.
            if (! $last->is_recurring || $last->user === null) {
                continue;
            }

            $isAutomatic = in_array($last->payment_method, [
                PaymentMethod::AUTO_DEBIT->value,
                PaymentMethod::CREDIT_CARD->value,
            ], true);

            // Automáticos só entram na data; os demais entram com antecedência.
            $limit = $isAutomatic ? $today : $horizon;
            // Índice da próxima ocorrência a partir da âncora (1ª data da série).
            $k = 1;
            while ($this->occurrence($anchor, $k, $last->recurring_frequency)->lte($last->date)) {
                $k++;
            }
            $next = $this->occurrence($anchor, $k, $last->recurring_frequency);
            $generated = 0;
            // Limite mensal de transações do plano do dono da série.
            // Planos ilimitados retornam PHP_INT_MAX: pulamos a contagem.
            $cap = $last->user->plan_limits['transactions_per_month'];
            $unlimited = $cap === PHP_INT_MAX;

            while ($next->lte($limit) && $generated < self::MAX_PER_SERIES) {
                // Plano Free: não ultrapassa o limite mensal de transações.
                if (! $unlimited && $this->monthlyCount($last->user, $next) >= $cap) {
                    Log::info('Recurring transaction skipped: monthly plan limit reached', [
                        'user_id' => $last->user_id,
                        'series_id' => $seriesId,
                        'date' => $next->toDateString(),
                        'limit' => $cap,
                    ]);
                    $this->warn(sprintf(
                        '[limite] %s | %s | limite mensal do plano atingido',
                        $next->format('d/m/Y'),
                        $last->description
                    ));

                    $skipped++;
                    $generated++;
                    $next = $this->occurrence($anchor, ++$k, $last->recurring_frequency);

                    continue;
                }

                $status = $isAutomatic ? TransactionStatus::PAID : TransactionStatus::PENDING;

                $this->line(sprintf(
                    '%s %s | %s | R$ %s | %s',
                    $dryRun ? '[dry-run]' : '[criada]',
                    $next->format('d/m/Y'),
                    $last->description,
                    number_format((float) $last->amount, 2, ',', '.'),
                    $status->label($last->type)
                ));

                if (! $dryRun) {
                    $transactionService->createTransaction(TransactionDTO::fromArray([
                        'user_id' => $last->user_id,
                        'wallet_id' => $last->wallet_id,
                        'category_id' => $last->category_id,
                        'type' => $last->type,
                        'status' => $status->value,
                        'amount' => $last->amount,
                        'description' => $last->description,
                        'date' => $next->toDateString(),
                        'due_date' => $next->toDateString(),
                        'payment_method' => $last->payment_method,
                        'is_recurring' => true,
                        'recurring_frequency' => $last->recurring_frequency,
                        'recurring_source_id' => $seriesId,
                        'origin' => Transaction::ORIGIN_RECURRING,
                        'notes' => $last->notes,
                    ]));
                }

                if (! $unlimited) {
                    $this->monthlyCounts[$last->user_id][$next->format('Y-m')]++;
                }

                $created++;
                $generated++;
                $next = $this->occurrence($anchor, ++$k, $last->recurring_frequency);
            }
        }

        $this->info("{$created} ocorrência(s) ".($dryRun ? 'seriam geradas.' : 'gerada(s).')
            .($skipped > 0 ? " {$skipped} ignorada(s) pelo limite mensal do plano." : ''));

        return Command::SUCCESS;
    }

    /**
     * Transações do usuário no mês de $date, contadas uma vez no banco e
     * depois mantidas em memória (inclusive no --dry-run).
     */
    private function monthlyCount(User $user, Carbon $date): int
    {
        return $this->monthlyCounts[$user->id][$date->format('Y-m')]
            ??= $user->transactionCountInMonth($date);
    }

    /**
     * k-ésima ocorrência calculada SEMPRE a partir da âncora.
     *
     * Ex.: âncora 31/01, mensal → 28/02, 31/03, 30/04...
     * (somando mês a mês a partir da anterior, a conta ficaria "presa" no dia 28).
     */
    private function occurrence(Carbon $anchor, int $k, string $frequency): Carbon
    {
        return match ($frequency) {
            'daily' => $anchor->copy()->addDays($k),
            'weekly' => $anchor->copy()->addWeeks($k),
            'yearly' => $anchor->copy()->addYearsNoOverflow($k),
            default => $anchor->copy()->addMonthsNoOverflow($k),
        };
    }
}
