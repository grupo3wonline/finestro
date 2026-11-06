@extends('layouts.app')

@section('title', 'Carteiras')

@section('breadcrumb')
    <x-ui.breadcrumb :items="[['label' => 'Carteiras']]" />
@endsection

@section('content')
<div class="space-y-6">
    <x-ui.page-header title="Minhas Carteiras" subtitle="Gerencie suas contas e carteiras financeiras." icon="fa-wallet">
        <x-slot:actions>
            <x-ui.button :href="route('wallets.create')" icon="fa-plus" loading="Carregando...">Nova Carteira</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.tip>
        <strong>Dica:</strong> Crie carteiras separadas para cada conta bancária, cartão ou investimento. Assim você terá uma visão clara de onde está seu dinheiro.
    </x-ui.tip>

    {{-- Convites pendentes --}}
    @if(isset($pendingInvitations) && $pendingInvitations->count() > 0)
    <x-ui.section-card title="Convites pendentes" icon="fa-envelope-open-text" iconColor="accent">
        <p class="text-sm text-surface-500 dark:text-surface-400 mb-4">
            Você foi convidado para acompanhar as carteiras abaixo. Aceite para visualizá-las junto com as suas.
        </p>
        <div class="space-y-3">
            @foreach($pendingInvitations as $invitation)
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 rounded-xl border border-accent-200 dark:border-accent-900/50 bg-accent-50/60 dark:bg-accent-900/10">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-surface-900 dark:text-white truncate">
                        <i class="fas fa-wallet text-accent-500 mr-1"></i> {{ $invitation->wallet->name }}
                    </p>
                    <p class="text-xs text-surface-500 dark:text-surface-400">
                        Convite de {{ $invitation->wallet->user->name ?? 'outro usuário' }}
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <form autocomplete="off" method="POST" action="{{ route('wallets.share.accept', $invitation) }}">
                        @csrf @method('PATCH')
                        <x-ui.button type="submit" variant="success" size="sm" icon="fa-check" loading="Aceitando...">Aceitar</x-ui.button>
                    </form>
                    <form autocomplete="off" method="POST" action="{{ route('wallets.share.destroy', $invitation) }}"
                        data-confirm="Recusar o convite para a carteira {{ $invitation->wallet->name }}?">
                        @csrf @method('DELETE')
                        <x-ui.button type="submit" variant="outline" size="sm" icon="fa-xmark">Recusar</x-ui.button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </x-ui.section-card>
    @endif

    <!-- Wallets Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($wallets as $wallet)
        <div class="bg-white dark:bg-surface-900 rounded-2xl p-6 shadow-card border border-surface-100 dark:border-surface-800 hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: {{ $wallet->color }}20">
                    <i class="fas {{ $wallet->icon }} text-xl" style="color: {{ $wallet->color }}"></i>
                </div>
                <div class="relative flex items-center gap-1" x-data="{ open: false }">
                    <button @click="open = !open" class="p-2 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-500" aria-label="Ações da carteira">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div x-show="open" x-cloak @click.away="open = false" x-transition.origin.top.right class="absolute top-full right-0 mt-1 bg-white dark:bg-surface-800 rounded-xl shadow-lg border border-surface-200 dark:border-surface-700 py-1 z-10 w-36">
                        <a href="{{ route('wallets.show', $wallet) }}" class="block px-4 py-2 text-sm hover:bg-surface-100 dark:hover:bg-surface-700"><i class="fas fa-eye mr-2 text-surface-400"></i>Ver</a>
                        <a href="{{ route('wallets.edit', $wallet) }}" class="block px-4 py-2 text-sm hover:bg-surface-100 dark:hover:bg-surface-700"><i class="fas fa-edit mr-2 text-surface-400"></i>Editar</a>
                        <form autocomplete="off" action="{{ route('wallets.destroy', $wallet) }}" method="POST" data-confirm="Tem certeza que deseja excluir esta carteira? Esta ação não pode ser desfeita.">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/10"><i class="fas fa-trash mr-2"></i>Excluir</button>
                        </form>
                    </div>
                </div>
            </div>
            <h3 class="font-semibold text-surface-900 dark:text-white mb-1">{{ $wallet->name }}</h3>
            <div class="flex items-center gap-2 mb-3">
                <p class="text-xs text-surface-500 dark:text-surface-400">{{ ucfirst($wallet->type) }}</p>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $wallet->ownership === 'business' ? 'bg-accent-100 dark:bg-accent-900/30 text-accent-800 dark:text-accent-400' : 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400' }}">
                    <i class="fas {{ $wallet->ownership === 'business' ? 'fa-building' : 'fa-user' }} mr-1 text-[10px]"></i>
                    {{ $wallet->ownership === 'business' ? 'Empresa' : 'Pessoal' }}
                </span>
            </div>
            <p class="num text-2xl font-semibold text-surface-900 dark:text-white">R$ {{ number_format($wallet->balance, 2, ',', '.') }}</p>
            @if($wallet->isCreditCard() && $wallet->credit_limit)
            <div class="mt-2">
                <div class="flex justify-between text-xs text-surface-500 dark:text-surface-400 mb-1">
                    <span>Limite: R$ {{ number_format($wallet->credit_limit, 2, ',', '.') }}</span>
                    <span class="num">{{ number_format($wallet->credit_usage_percent, 0) }}% usado</span>
                </div>
                <div class="w-full bg-surface-200 dark:bg-surface-700 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full {{ $wallet->credit_usage_percent > 80 ? 'bg-red-500' : ($wallet->credit_usage_percent > 50 ? 'bg-accent-500' : 'bg-primary-500') }}" style="width: {{ min(100, $wallet->credit_usage_percent) }}%"></div>
                </div>
            </div>
            @endif
            @if($wallet->isBusiness() && $wallet->company_name)
            <p class="text-xs text-accent-700 dark:text-accent-400 mt-2"><i class="fas fa-building mr-1"></i> {{ $wallet->company_name }}</p>
            @endif
            @if($wallet->is_shared)
            <p class="text-xs text-primary-600 dark:text-primary-400 mt-1"><i class="fas fa-users mr-1"></i> Compartilhada</p>
            @endif
            @if($wallet->description)
            <p class="text-xs text-surface-500 dark:text-surface-400 mt-2">{{ Str::limit($wallet->description, 60) }}</p>
            @endif
        </div>
        @empty
        <div class="col-span-full bg-white dark:bg-surface-900 rounded-2xl border border-surface-100 dark:border-surface-800">
            <x-ui.empty-state icon="fa-wallet" title="Nenhuma carteira cadastrada"
                text="Comece criando sua primeira carteira para organizar suas finanças.">
                <x-slot:action>
                    <x-ui.button :href="route('wallets.create')" icon="fa-plus" loading="Carregando...">Criar Carteira</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        </div>
        @endforelse
    </div>

    <!-- Shared Wallets Section -->
    @if(isset($sharedWallets) && $sharedWallets->count() > 0)
    <div class="mt-8">
        <h2 class="text-lg font-semibold text-surface-900 dark:text-white mb-4">
            <i class="fas fa-share-alt text-accent-500 mr-2"></i> Carteiras Compartilhadas Comigo
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($sharedWallets as $wallet)
            <div class="bg-white dark:bg-surface-900 rounded-2xl p-6 shadow-card border-2 border-accent-200 dark:border-accent-900/50 hover:shadow-card-hover transition relative">
                <div class="absolute top-2 right-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-accent-100 dark:bg-accent-900/30 text-accent-800 dark:text-accent-400">
                        <i class="fas fa-share-alt mr-1 text-[10px]"></i> Compartilhada
                    </span>
                </div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: {{ $wallet->color }}20">
                        <i class="fas {{ $wallet->icon }} text-xl" style="color: {{ $wallet->color }}"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-surface-900 dark:text-white"><a href="{{ route('wallets.show', $wallet) }}" class="hover:underline">{{ $wallet->name }}</a></h3>
                        <p class="text-xs text-surface-500 dark:text-surface-400">{{ $wallet->company_name ?? ucfirst($wallet->type) }}</p>
                    </div>
                </div>
                <p class="num text-2xl font-semibold text-surface-900 dark:text-white">R$ {{ number_format($wallet->balance, 2, ',', '.') }}</p>
                <p class="text-xs text-surface-500 dark:text-surface-400 mt-2">
                    <i class="fas fa-user mr-1"></i> Proprietário: {{ $wallet->user->name ?? 'N/A' }}
                </p>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
