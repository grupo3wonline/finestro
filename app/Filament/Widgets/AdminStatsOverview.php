<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Interfaces\Services\AdminMetricsServiceInterface;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dashboard numbers (counts and totals only, no per-user financial data).
 */
class AdminStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected ?string $heading = 'Visão geral';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('admin.access');
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $metrics = app(AdminMetricsServiceInterface::class)->metrics();

        return [
            Stat::make('Usuários', (string) $metrics->totalUsers)
                ->description("{$metrics->newUsersLast30Days} novos nos últimos 30 dias"),
            Stat::make('Free × Profissional', "{$metrics->freeUsers} × {$metrics->professionalUsers}")
                ->description('Usuários por plano atual'),
            Stat::make('Assinaturas ativas', (string) $metrics->activeSubscriptions)
                ->description("{$metrics->pastDueSubscriptions} com pagamento pendente · {$metrics->canceledSubscriptions} canceladas")
                ->color('success'),
            Stat::make('MRR estimado', $metrics->mrrFormatted)
                ->description('Estimativa: assinaturas ativas × preço regular do plano'),
            Stat::make('Pagamentos no mês', $metrics->paymentsThisMonthFormatted)
                ->description("{$metrics->paymentsThisMonthCount} pagamentos confirmados"),
        ];
    }
}
