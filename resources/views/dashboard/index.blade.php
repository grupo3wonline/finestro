@extends('layouts.app')

@section('title', 'Painel')

@section('breadcrumb')
    <x-ui.breadcrumb :items="[['label' => 'Painel']]" />
@endsection

@section('content')
@php $monthBalance = $totalIncome - $totalExpenses; @endphp
<div class="space-y-6">
    <!-- Greeting -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-surface-900 dark:text-white">
                {{ $greeting }}, {{ Str::before(Auth::user()->name, ' ') }}! <span class="text-2xl">👋</span>
            </h1>
            <p class="text-surface-500 dark:text-surface-300">Aqui está o resumo das suas finanças este mês.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('subscription.index') }}" class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium {{ Auth::user()->isProfessional() ? 'bg-accent-100 dark:bg-accent-900/30 text-accent-800 dark:text-accent-300' : 'bg-surface-100 dark:bg-surface-800 text-surface-600 dark:text-surface-300' }} hover:opacity-80 transition" title="Seu plano atual">
                <i class="fas {{ Auth::user()->isProfessional() ? 'fa-crown text-accent-500' : 'fa-user' }} mr-1.5"></i>
                {{ Auth::user()->isProfessional() ? 'Profissional' : 'Básico' }}
            </a>
        </div>
    </div>

    <!-- Hero: total balance is the page's centrepiece -->
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary-800 via-primary-700 to-primary-900 text-white shadow-float">
        {{-- soft radial highlight, not a decorative gradient wash --}}
        <div class="pointer-events-none absolute -top-24 -right-16 w-72 h-72 rounded-full bg-primary-400/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -left-10 w-80 h-80 rounded-full bg-secondary-500/10 blur-3xl"></div>

        <div class="relative p-6 sm:p-8 lg:p-10 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-8">
            <div class="min-w-0">
                <p class="text-primary-100/80 text-sm font-medium flex items-center gap-2">
                    <i class="fas fa-wallet"></i> Saldo total em {{ $wallets->count() }} carteira(s)
                </p>
                <p class="num text-4xl sm:text-5xl lg:text-6xl font-semibold mt-3 leading-none tracking-tight">
                    <span class="text-primary-200/70 text-2xl sm:text-3xl align-top mr-1">R$</span>{{ number_format($totalBalance, 2, ',', '.') }}
                </p>
                <p class="mt-4 inline-flex items-center gap-2 text-sm {{ $monthBalance >= 0 ? 'text-secondary-200' : 'text-accent-200' }}">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-white/10 backdrop-blur">
                        <i class="fas {{ $monthBalance >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                        {{ $monthBalance >= 0 ? '+' : '' }}R$ {{ number_format($monthBalance, 2, ',', '.') }} este mês
                    </span>
                </p>
            </div>
            <div class="shrink-0">
                <a href="{{ route('transactions.create') }}" class="inline-flex items-center justify-center px-5 py-3 bg-accent-400 hover:bg-accent-300 text-surface-950 text-sm font-bold rounded-xl transition shadow-lg shadow-accent-500/25 focus:outline-none focus:ring-2 focus:ring-accent-300 focus:ring-offset-2 focus:ring-offset-primary-800">
                    <i class="fas fa-plus mr-2"></i> Nova transação
                </a>
            </div>
        </div>
    </section>

    <!-- Supporting stats: income / expenses / projected -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-surface-900 rounded-2xl p-5 shadow-card border border-surface-100 dark:border-surface-800">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-surface-500 dark:text-surface-300">Receitas do mês</span>
                <span class="w-9 h-9 bg-primary-50 dark:bg-primary-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-arrow-down-long text-primary-600 rotate-180 text-sm"></i>
                </span>
            </div>
            <p class="num text-2xl font-semibold text-primary-600 dark:text-primary-400">R$ {{ number_format($totalIncome, 2, ',', '.') }}</p>
            <p class="text-xs text-surface-400 dark:text-surface-500 mt-1">Entradas confirmadas</p>
        </div>

        <div class="bg-white dark:bg-surface-900 rounded-2xl p-5 shadow-card border border-surface-100 dark:border-surface-800">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-surface-500 dark:text-surface-300">Despesas do mês</span>
                <span class="w-9 h-9 bg-red-50 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-arrow-down-long text-red-600 text-sm"></i>
                </span>
            </div>
            <p class="num text-2xl font-semibold text-red-600">R$ {{ number_format($totalExpenses, 2, ',', '.') }}</p>
            <p class="text-xs text-surface-400 dark:text-surface-500 mt-1">Saídas confirmadas</p>
        </div>

        <div class="bg-white dark:bg-surface-900 rounded-2xl p-5 shadow-card border border-surface-100 dark:border-surface-800">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-surface-500 dark:text-surface-300">Balanço previsto</span>
                <span class="w-9 h-9 bg-accent-50 dark:bg-accent-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-pie text-accent-600 text-sm"></i>
                </span>
            </div>
            <p class="num text-2xl font-semibold {{ $summary['balance_projected'] >= 0 ? 'text-surface-900 dark:text-white' : 'text-red-600' }}">R$ {{ number_format($summary['balance_projected'], 2, ',', '.') }}</p>
            <p class="text-xs text-surface-400 dark:text-surface-500 mt-1">Realizado + contas em aberto</p>
        </div>
    </div>

    <!-- Gráficos: fluxo do mês + distribuição por carteira -->
    @if($totalIncome > 0 || $totalExpenses > 0 || $wallets->sum('balance') > 0)
    <div class="grid lg:grid-cols-5 gap-6">
        <!-- Receitas vs Despesas -->
        <x-ui.section-card title="Receitas vs Despesas" icon="fa-chart-column" class="lg:col-span-3">
            <div class="h-64">
                <canvas id="chart-flow"></canvas>
            </div>
        </x-ui.section-card>

        <!-- Distribuição por carteira -->
        <x-ui.section-card title="Saldo por carteira" icon="fa-chart-pie" iconColor="secondary" class="lg:col-span-2">
            @if($wallets->where('balance', '>', 0)->count() > 0)
                <div class="h-64">
                    <canvas id="chart-wallets"></canvas>
                </div>
            @else
                <div class="h-64 flex flex-col items-center justify-center text-center">
                    <i class="fas fa-chart-pie text-3xl text-surface-300 dark:text-surface-600 mb-2"></i>
                    <p class="text-sm text-surface-500 dark:text-surface-400">Sem saldo positivo para exibir.</p>
                </div>
            @endif
        </x-ui.section-card>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const { chart, brl, palette } = window.Finestro;

            // Receitas vs Despesas (barras)
            const flowEl = document.getElementById('chart-flow');
            if (flowEl) {
                chart(flowEl, {
                    type: 'bar',
                    data: {
                        labels: ['Este mês'],
                        datasets: [
                            { label: 'Receitas', data: [{{ $totalIncome }}], backgroundColor: palette.primary, borderRadius: 8, maxBarThickness: 64 },
                            { label: 'Despesas', data: [{{ $totalExpenses }}], backgroundColor: palette.red, borderRadius: 8, maxBarThickness: 64 },
                        ],
                    },
                    options: {
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + brl(c.raw) } },
                        },
                        scales: {
                            y: { beginAtZero: true, ticks: { callback: (v) => brl(v) } },
                            x: { grid: { display: false } },
                        },
                    },
                });
            }

            // Distribuição de saldo por carteira (rosca)
            const walletsEl = document.getElementById('chart-wallets');
            if (walletsEl) {
                @php
                    $positiveWallets = $wallets->where('balance', '>', 0);
                    $walletColors = $positiveWallets->pluck('color')->filter()->values()->all();
                @endphp
                const walletColors = @json($walletColors);
                chart(walletsEl, {
                    type: 'doughnut',
                    data: {
                        labels: @json($positiveWallets->pluck('name')->values()),
                        datasets: [{
                            data: @json($positiveWallets->pluck('balance')->values()),
                            backgroundColor: walletColors.length ? walletColors : palette.series,
                            borderWidth: 0,
                            hoverOffset: 6,
                        }],
                    },
                    options: {
                        cutout: '62%',
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: { callbacks: { label: (c) => c.label + ': ' + brl(c.raw) } },
                        },
                    },
                });
            }
        });
    </script>
    @endpush
    @endif

    <!-- Contas e alertas de orçamento -->
    @if($upcomingBills->isNotEmpty() || $budgetAlerts->isNotEmpty())
    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Próximas contas -->
        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border {{ $overdueCount ? 'border-red-200 dark:border-red-900/50' : 'border-surface-100 dark:border-surface-800' }} {{ $budgetAlerts->isEmpty() ? 'lg:col-span-2' : '' }}">
            <div class="p-6 border-b border-surface-100 dark:border-surface-800 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-surface-900 dark:text-white">
                    <i class="fas fa-file-invoice-dollar text-primary-500 mr-2"></i> Contas próximas
                    @if($overdueCount)
                        <span class="ml-2 text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">{{ $overdueCount }} atrasada(s)</span>
                    @endif
                </h2>
                <a href="{{ route('bills.index') }}" class="text-sm text-primary-600 hover:underline">Ver todas</a>
            </div>
            @forelse($upcomingBills->take(6) as $bill)
                <div class="px-6 py-3 flex items-center justify-between gap-3 border-b last:border-0 border-surface-100 dark:border-surface-800">
                    <div class="min-w-0">
                        <a href="{{ route('transactions.show', $bill) }}" class="text-sm font-medium text-surface-900 dark:text-white hover:text-primary-600 truncate block">{{ $bill->description }}</a>
                        <p class="text-xs {{ $bill->is_overdue ? 'text-red-600' : 'text-surface-500' }}">
                            {{ $bill->isIncome() ? 'Receber' : 'Pagar' }} até {{ $bill->effective_due_date->format('d/m') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="num text-sm font-semibold {{ $bill->isIncome() ? 'text-primary-600 dark:text-primary-400' : 'text-red-600' }}">R$ {{ number_format((float) $bill->amount, 2, ',', '.') }}</span>
                        <x-tx.pay-button :transaction="$bill" compact />
                    </div>
                </div>
            @empty
                <p class="px-6 py-8 text-center text-sm text-surface-500"><i class="fas fa-circle-check text-primary-500 mr-1"></i> Nenhuma conta vencendo nos próximos 7 dias.</p>
            @endforelse
        </div>

        <!-- Alertas de orçamento -->
        @if($budgetAlerts->isNotEmpty())
        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-accent-200 dark:border-accent-900/50">
            <div class="p-6 border-b border-surface-100 dark:border-surface-800 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-surface-900 dark:text-white">
                    <i class="fas fa-scale-unbalanced text-accent-500 mr-2"></i> Orçamentos em alerta
                </h2>
                <a href="{{ route('budgets.index') }}" class="text-sm text-primary-600 hover:underline">Ver orçamentos</a>
            </div>
            <div class="p-6 space-y-4">
                @foreach($budgetAlerts->take(4) as $budget)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-surface-700 dark:text-surface-300">{{ $budget->label }}</span>
                            <span class="num {{ $budget->health === 'exceeded' ? 'text-red-600' : 'text-accent-600' }} font-semibold">{{ number_format($budget->percent, 0) }}%</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-surface-200 dark:bg-surface-800">
                            <div class="h-2 rounded-full {{ $budget->health === 'exceeded' ? 'bg-red-500' : 'bg-accent-500' }}" style="width: {{ min(100, $budget->percent) }}%"></div>
                        </div>
                        <p class="text-xs text-surface-500 mt-1">R$ {{ number_format($budget->spent, 2, ',', '.') }} de R$ {{ number_format((float) $budget->amount, 2, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Wallets & Goals -->
    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Wallets -->
        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800">
            <div class="p-6 border-b border-surface-100 dark:border-surface-800 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-surface-900 dark:text-white">
                    <i class="fas fa-wallet text-primary-500 mr-2"></i> Minhas Carteiras
                </h2>
                <a href="{{ route('wallets.create') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    <i class="fas fa-plus mr-1"></i> Nova
                </a>
            </div>
            <div class="p-6">
                @forelse($wallets as $wallet)
                <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-surface-100 dark:border-surface-800' : '' }}">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background-color: {{ $wallet->color }}20">
                            <i class="fas {{ $wallet->icon }}" style="color: {{ $wallet->color }}"></i>
                        </div>
                        <div>
                            <p class="font-medium text-surface-900 dark:text-white text-sm">{{ $wallet->name }}</p>
                            <p class="text-xs text-surface-500 dark:text-surface-400">{{ ucfirst($wallet->type) }}</p>
                        </div>
                    </div>
                    <span class="num font-semibold text-surface-900 dark:text-white text-sm">
                        R$ {{ number_format($wallet->balance, 2, ',', '.') }}
                    </span>
                </div>
                @empty
                <div class="text-center py-8">
                    <i class="fas fa-wallet text-4xl text-surface-300 dark:text-surface-600 mb-3"></i>
                    <p class="text-surface-500 dark:text-surface-400">Nenhuma carteira cadastrada.</p>
                    <a href="{{ route('wallets.create') }}" class="text-primary-600 hover:underline text-sm mt-2 inline-block">Criar primeira carteira</a>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Goals -->
        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800">
            <div class="p-6 border-b border-surface-100 dark:border-surface-800 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-surface-900 dark:text-white">
                    <i class="fas fa-bullseye text-accent-500 mr-2"></i> Metas Ativas
                </h2>
                <a href="{{ route('goals.create') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    <i class="fas fa-plus mr-1"></i> Nova
                </a>
            </div>
            <div class="p-6">
                @forelse($goals as $goal)
                <div class="py-3 {{ !$loop->last ? 'border-b border-surface-100 dark:border-surface-800' : '' }}">
                    <div class="flex items-center justify-between mb-2">
                        <p class="font-medium text-surface-900 dark:text-white text-sm">{{ $goal->name }}</p>
                        <span class="num text-xs text-surface-500 dark:text-surface-400">{{ $goal->progress }}%</span>
                    </div>
                    <div class="w-full bg-surface-200 dark:bg-surface-800 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all" style="width: {{ $goal->progress }}%; background-color: {{ $goal->color }}"></div>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span class="num text-xs text-surface-500">R$ {{ number_format($goal->current_amount, 2, ',', '.') }}</span>
                        <span class="num text-xs text-surface-500">R$ {{ number_format($goal->target_amount, 2, ',', '.') }}</span>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <i class="fas fa-bullseye text-4xl text-surface-300 dark:text-surface-600 mb-3"></i>
                    <p class="text-surface-500 dark:text-surface-400">Nenhuma meta ativa.</p>
                    <a href="{{ route('goals.create') }}" class="text-primary-600 hover:underline text-sm mt-2 inline-block">Criar primeira meta</a>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 overflow-hidden">
        <div class="p-6 border-b border-surface-100 dark:border-surface-800 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-surface-900 dark:text-white">
                <i class="fas fa-exchange-alt text-secondary-600 mr-2"></i> Transações Recentes
            </h2>
            <a href="{{ route('transactions.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">Ver todas</a>
        </div>
        <div class="overflow-x-auto">
            @if($transactions->count() > 0)
            <table class="w-full">
                <thead class="bg-surface-50 dark:bg-surface-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase">Descrição</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 dark:text-surface-400 uppercase">Carteira</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-surface-500 dark:text-surface-400 uppercase">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                    @foreach($transactions->take(5) as $transaction)
                    <tr class="hover:bg-surface-50 dark:hover:bg-surface-800/40">
                        <td class="px-6 py-4 text-sm text-surface-600 dark:text-surface-400">{{ $transaction->date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 text-sm text-surface-900 dark:text-white">{{ $transaction->description }}</td>
                        <td class="px-6 py-4 text-sm text-surface-600 dark:text-surface-400">{{ $transaction->wallet->name ?? '-' }}</td>
                        <td class="px-6 py-4 num text-sm text-right font-semibold {{ $transaction->type === 'income' ? 'text-primary-600 dark:text-primary-400' : 'text-red-600' }}">
                            {{ $transaction->type === 'income' ? '+' : '-' }} R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="text-center py-12">
                <i class="fas fa-receipt text-4xl text-surface-300 dark:text-surface-600 mb-3"></i>
                <p class="text-surface-500 dark:text-surface-400">Nenhuma transação registrada este mês.</p>
                <a href="{{ route('transactions.create') }}" class="text-primary-600 hover:underline text-sm mt-2 inline-block">Registrar primeira transação</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
