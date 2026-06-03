@extends('layouts.app')

@section('title', 'Minha Assinatura')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Assinatura</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Minha Assinatura</h1>
            <p class="text-gray-600 dark:text-gray-400 text-sm">Gerencie seu plano, vigência e histórico de pagamentos.</p>
        </div>
        <a href="{{ route('subscription.plans') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
            <i class="fas fa-arrow-up mr-2"></i> {{ $user->isProfessional() ? 'Gerenciar Plano' : 'Fazer Upgrade' }}
        </a>
    </div>

    <!-- Current Plan Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 {{ $user->isProfessional() ? 'bg-gradient-to-r from-primary-600 to-primary-800 text-white' : 'bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600' }}">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm {{ $user->isProfessional() ? 'text-primary-200' : 'text-gray-500 dark:text-gray-400' }}">Plano Atual</p>
                    <h2 class="text-2xl font-bold {{ $user->isProfessional() ? 'text-white' : 'text-gray-900 dark:text-white' }}">
                        {{ $user->isProfessional() ? 'Profissional' : 'Básico (Gratuito)' }}
                    </h2>
                    @if($user->isProfessional())
                    <p class="text-primary-200 text-sm mt-1">R$ 29,90/mês</p>
                    @endif
                </div>
                <div class="w-16 h-16 {{ $user->isProfessional() ? 'bg-white/20' : 'bg-gray-300 dark:bg-gray-500' }} rounded-full flex items-center justify-center">
                    <i class="fas {{ $user->isProfessional() ? 'fa-crown' : 'fa-user' }} text-2xl {{ $user->isProfessional() ? 'text-yellow-300' : 'text-gray-600 dark:text-gray-300' }}"></i>
                </div>
            </div>
        </div>

        @if($subscription && $subscription->current_period_end)
        <div class="p-6 border-t border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Subscription Status -->
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Status</p>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                        {{ $subscription->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                        {{ $subscription->status === 'canceled' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                        {{ $subscription->status === 'past_due' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}">
                        @if($subscription->status === 'active') <i class="fas fa-check-circle mr-1"></i> Ativa @endif
                        @if($subscription->status === 'canceled') <i class="fas fa-times-circle mr-1"></i> Cancelada @endif
                        @if($subscription->status === 'past_due') <i class="fas fa-exclamation-circle mr-1"></i> Pagamento Pendente @endif
                    </span>
                </div>

                <!-- Period -->
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Vigência Atual</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $subscription->current_period_start->format('d/m/Y') }} - {{ $subscription->current_period_end->format('d/m/Y') }}
                    </p>
                </div>

                <!-- Countdown -->
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg" x-data="countdown('{{ $subscription->current_period_end->toIso8601String() }}')">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Próxima Renovação</p>
                    <p class="text-lg font-bold text-primary-600" x-text="timeLeft"></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="label"></p>
                </div>
            </div>

            @if($subscription->status === 'active')
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <form action="{{ route('subscription.cancel') }}" method="POST" onsubmit="return confirm('Tem certeza que deseja cancelar sua assinatura? Você terá acesso até o fim do período atual.')">
                    @csrf
                    <button type="submit" class="text-sm text-red-600 hover:text-red-700 font-medium">
                        <i class="fas fa-times mr-1"></i> Cancelar Assinatura
                    </button>
                </form>
            </div>
            @endif
        </div>
        @else
        <div class="p-6 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Você está no plano gratuito. <a href="{{ route('subscription.plans') }}" class="text-primary-600 hover:underline font-medium">Faça upgrade</a> para desbloquear todos os recursos.
            </p>
        </div>
        @endif
    </div>

    <!-- Plan Limits Usage -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-chart-bar text-primary-500 mr-2"></i> Uso do Plano
            </h2>
        </div>
        <div class="p-6">
            @php
                $limits = $user->plan_limits;
                $walletsCount = $user->wallets()->count();
                $goalsCount = $user->goals()->count();
                $transactionsCount = $user->transactions()->whereMonth('date', now()->month)->whereYear('date', now()->year)->count();
                $categoriesCount = \App\Models\Category::where('user_id', $user->id)->count();
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Wallets -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><i class="fas fa-wallet mr-1"></i> Carteiras</span>
                        <span class="text-xs text-gray-500">{{ $walletsCount }}/{{ $limits['wallets'] === PHP_INT_MAX ? '∞' : $limits['wallets'] }}</span>
                    </div>
                    @if($limits['wallets'] !== PHP_INT_MAX)
                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                        <div class="bg-primary-500 h-2 rounded-full" style="width: {{ min(100, ($walletsCount / $limits['wallets']) * 100) }}%"></div>
                    </div>
                    @else
                    <p class="text-xs text-green-600">Ilimitado</p>
                    @endif
                </div>

                <!-- Transactions -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><i class="fas fa-exchange-alt mr-1"></i> Transações/mês</span>
                        <span class="text-xs text-gray-500">{{ $transactionsCount }}/{{ $limits['transactions_per_month'] === PHP_INT_MAX ? '∞' : $limits['transactions_per_month'] }}</span>
                    </div>
                    @if($limits['transactions_per_month'] !== PHP_INT_MAX)
                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                        <div class="bg-secondary-500 h-2 rounded-full" style="width: {{ min(100, ($transactionsCount / $limits['transactions_per_month']) * 100) }}%"></div>
                    </div>
                    @else
                    <p class="text-xs text-green-600">Ilimitado</p>
                    @endif
                </div>

                <!-- Goals -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><i class="fas fa-bullseye mr-1"></i> Metas</span>
                        <span class="text-xs text-gray-500">{{ $goalsCount }}/{{ $limits['goals'] === PHP_INT_MAX ? '∞' : $limits['goals'] }}</span>
                    </div>
                    @if($limits['goals'] !== PHP_INT_MAX)
                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                        <div class="bg-purple-500 h-2 rounded-full" style="width: {{ min(100, ($goalsCount / $limits['goals']) * 100) }}%"></div>
                    </div>
                    @else
                    <p class="text-xs text-green-600">Ilimitado</p>
                    @endif
                </div>

                <!-- Categories -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><i class="fas fa-tags mr-1"></i> Categorias</span>
                        <span class="text-xs text-gray-500">{{ $categoriesCount }}/{{ $limits['categories'] === PHP_INT_MAX ? '∞' : $limits['categories'] }}</span>
                    </div>
                    @if($limits['categories'] !== PHP_INT_MAX)
                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                        <div class="bg-orange-500 h-2 rounded-full" style="width: {{ min(100, ($categoriesCount / $limits['categories']) * 100) }}%"></div>
                    </div>
                    @else
                    <p class="text-xs text-green-600">Ilimitado</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Payment History -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-history text-secondary-500 mr-2"></i> Histórico de Pagamentos
            </h2>
        </div>
        <div class="overflow-x-auto">
            @if($payments->count() > 0)
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Descrição</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($payments as $payment)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : $payment->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $payment->description }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                {{ $payment->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                {{ $payment->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                {{ $payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                {{ $payment->status === 'refunded' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400' : '' }}">
                                {{ $payment->status === 'paid' ? 'Pago' : '' }}
                                {{ $payment->status === 'failed' ? 'Falhou' : '' }}
                                {{ $payment->status === 'pending' ? 'Pendente' : '' }}
                                {{ $payment->status === 'refunded' ? 'Reembolsado' : '' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900 dark:text-white">
                            R$ {{ number_format($payment->amount, 2, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="text-center py-12">
                <i class="fas fa-receipt text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                <p class="text-gray-500 dark:text-gray-400">Nenhum pagamento registrado.</p>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function countdown(endDate) {
    return {
        timeLeft: '',
        label: '',
        init() {
            this.update();
            setInterval(() => this.update(), 1000);
        },
        update() {
            const end = new Date(endDate);
            const now = new Date();
            const diff = end - now;

            if (diff <= 0) {
                this.timeLeft = 'Vencido';
                this.label = 'Renovação pendente';
                return;
            }

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            if (days > 0) {
                this.timeLeft = days + 'd ' + hours + 'h';
                this.label = 'para renovação';
            } else {
                this.timeLeft = hours + 'h ' + minutes + 'min';
                this.label = 'para renovação';
            }
        }
    }
}
</script>
@endpush
@endsection
