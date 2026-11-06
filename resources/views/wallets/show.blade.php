@extends('layouts.app')

@section('title', $wallet->name)

@section('breadcrumb')
<x-ui.breadcrumb :items="[
    ['label' => 'Carteiras', 'url' => route('wallets.index')],
    ['label' => $wallet->name],
]" />
@endsection

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center" style="background-color: {{ $wallet->color }}20">
                    <i class="fas {{ $wallet->icon }} text-2xl" style="color: {{ $wallet->color }}"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-surface-900 dark:text-white">{{ $wallet->name }}</h1>
                    @php
                        $typeLabels = ['checking' => 'Conta corrente', 'savings' => 'Poupança', 'credit' => 'Cartão de crédito', 'investment' => 'Investimentos', 'cash' => 'Dinheiro'];
                    @endphp
                    <p class="text-surface-500 dark:text-surface-400">{{ $typeLabels[$wallet->type] ?? ucfirst($wallet->type) }} • {{ $wallet->currency }}</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($wallet->isCreditCard())
                    @can('invoice', $wallet)
                    <a href="{{ route('wallets.invoice', $wallet) }}" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-xl transition">
                        <i class="fas fa-file-invoice mr-1"></i> Ver fatura
                    </a>
                    @endcan
                @endif
                @can('update', $wallet)
                <a href="{{ route('transactions.create', ['wallet_id' => $wallet->id]) }}" class="px-4 py-2 text-sm font-medium text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-xl transition">
                    <i class="fas fa-plus mr-1"></i> Lançar
                </a>
                <a href="{{ route('wallets.edit', $wallet) }}" class="px-4 py-2 text-sm font-medium text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-xl transition">
                    <i class="fas fa-edit mr-1"></i> Editar
                </a>
                @endcan
            </div>
        </div>

        <div class="grid sm:grid-cols-3 gap-4">
            <div class="bg-surface-50 dark:bg-surface-800/60 rounded-xl p-4">
                @if($wallet->isCreditCard())
                    <p class="text-sm text-surface-500 dark:text-surface-400">Limite utilizado</p>
                    <p class="num text-2xl font-semibold text-surface-900 dark:text-white">R$ {{ number_format($wallet->credit_used, 2, ',', '.') }}</p>
                    @if($wallet->credit_limit)
                        <div class="w-full h-1.5 rounded-full bg-surface-200 dark:bg-surface-700 mt-2">
                            <div class="h-1.5 rounded-full {{ $wallet->credit_usage_percent > 80 ? 'bg-red-500' : ($wallet->credit_usage_percent > 50 ? 'bg-accent-500' : 'bg-primary-500') }}" style="width: {{ min(100, $wallet->credit_usage_percent) }}%"></div>
                        </div>
                        <p class="text-xs text-surface-500 mt-1">Disponível: R$ {{ number_format($wallet->available_credit, 2, ',', '.') }} de R$ {{ number_format($wallet->credit_limit, 2, ',', '.') }}</p>
                    @endif
                @else
                    <p class="text-sm text-surface-500 dark:text-surface-400">Saldo Atual</p>
                    <p class="num text-2xl font-semibold {{ $wallet->balance < 0 ? 'text-red-600' : 'text-surface-900 dark:text-white' }}">R$ {{ number_format($wallet->balance, 2, ',', '.') }}</p>
                @endif
            </div>
            <div class="bg-surface-50 dark:bg-surface-800/60 rounded-xl p-4">
                <p class="text-sm text-surface-500 dark:text-surface-400">Status</p>
                <p class="text-lg font-medium {{ $wallet->is_active ? 'text-primary-600' : 'text-red-600' }}">
                    <i class="fas {{ $wallet->is_active ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                    {{ $wallet->is_active ? 'Ativa' : 'Inativa' }}
                </p>
            </div>
            <div class="bg-surface-50 dark:bg-surface-800/60 rounded-xl p-4">
                <p class="text-sm text-surface-500 dark:text-surface-400">Criada em</p>
                <p class="num text-lg font-medium text-surface-900 dark:text-white">{{ $wallet->created_at->format('d/m/Y') }}</p>
            </div>
        </div>

        @if($wallet->description)
        <div class="mt-4 p-4 bg-surface-50 dark:bg-surface-800/60 rounded-xl">
            <p class="text-sm text-surface-600 dark:text-surface-400">{{ $wallet->description }}</p>
        </div>
        @endif

        @if($wallet->isCreditCard())
            @if($invoice)
                @can('invoice', $wallet)
                <a href="{{ route('wallets.invoice', $wallet) }}" class="mt-4 flex items-center justify-between p-4 rounded-xl border border-primary-200 dark:border-primary-900/50 bg-primary-50/60 dark:bg-primary-900/10 hover:bg-primary-50 transition">
                    <div>
                        <p class="text-sm text-surface-600 dark:text-surface-300">Fatura atual ({{ $invoice['label'] }}) · fecha {{ $invoice['cycle']['end']->format('d/m') }} · vence {{ $invoice['cycle']['due']->format('d/m') }}</p>
                        <p class="num text-xl font-semibold text-surface-900 dark:text-white">R$ {{ number_format($invoice['total'], 2, ',', '.') }}</p>
                    </div>
                    <i class="fas fa-chevron-right text-primary-500"></i>
                </a>
                @endcan
            @else
                @can('update', $wallet)
                <p class="mt-4 text-sm text-accent-700 dark:text-accent-400">
                    <i class="fas fa-circle-info mr-1"></i>
                    Informe o <a href="{{ route('wallets.edit', $wallet) }}" class="underline">dia de fechamento e de vencimento</a> para acompanhar a fatura deste cartão.
                </p>
                @endcan
            @endif
        @endif
    </div>

    {{-- Compartilhamento (somente dono) --}}
    @if($isOwner)
    <x-ui.section-card title="Compartilhamento" icon="fa-user-group" iconColor="secondary"
        x-data="{ showInvite: {{ $errors->hasAny(['email', 'role']) ? 'true' : 'false' }} }">
        <x-slot:actions>
            @if($canShare)
                <x-ui.button variant="outline" size="sm" icon="fa-user-plus"
                    x-on:click="showInvite = !showInvite"
                    x-bind:aria-expanded="showInvite.toString()">
                    Convidar pessoa
                </x-ui.button>
            @endif
        </x-slot:actions>

        @unless($canShare)
            <x-ui.alert variant="info" class="mb-4">
                O compartilhamento de carteiras faz parte do plano Profissional.
                <a href="{{ route('subscription.index') }}" class="font-semibold underline">Faça upgrade</a>
                para convidar outras pessoas a acompanhar esta carteira.
            </x-ui.alert>
        @endunless

        {{-- Formulário de convite --}}
        @if($canShare)
        <form autocomplete="off" method="POST" action="{{ route('wallets.share', $wallet) }}"
            x-show="showInvite" x-cloak x-transition.origin.top class="mb-6">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto_auto] gap-3 sm:items-end">
                <x-ui.field label="E-mail da pessoa" for="share_email" error="email"
                    hint="Ela precisa ter uma conta no Finestro.">
                    <x-ui.input type="email" name="email" id="share_email" icon="fa-envelope"
                        placeholder="pessoa@exemplo.com" :value="old('email')" error="email" required />
                </x-ui.field>

                <x-ui.field label="Acesso" for="share_role" error="role">
                    <x-ui.select name="role" id="share_role" error="role">
                        <option value="viewer" @selected(old('role') === 'viewer')>Apenas visualizar</option>
                        <option value="editor" @selected(old('role') === 'editor')>Visualizar e editar</option>
                        <option value="admin" @selected(old('role') === 'admin')>Administrar</option>
                    </x-ui.select>
                </x-ui.field>

                <x-ui.button type="submit" icon="fa-paper-plane" loading="Enviando...">Enviar convite</x-ui.button>
            </div>
            <x-ui.tip icon="fa-circle-info" class="mt-3">
                O convidado recebe acesso somente depois de aceitar o convite. Quem ainda não
                tem conta recebe um convite por e-mail para se cadastrar. Membros com acesso
                aceito veem todos os lançamentos da carteira.
            </x-ui.tip>
        </form>
        @endif

        {{-- Lista de membros --}}
        @php
            $roleLabels = ['viewer' => 'Visualizar', 'editor' => 'Editar', 'admin' => 'Administrar'];
        @endphp
        @forelse($members as $member)
            @php
                // Unclaimed (email-only) invites have no linked user yet.
                $memberName = $member->user?->name ?? $member->email ?? 'Convidado';
                $memberEmail = $member->user?->email ?? $member->email;
            @endphp
            <div class="flex items-center justify-between gap-3 py-3 border-b last:border-0 border-surface-100 dark:border-surface-800">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-full bg-surface-100 dark:bg-surface-800 flex items-center justify-center shrink-0">
                        <i class="fas fa-user text-surface-400"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-surface-900 dark:text-white truncate">{{ $memberName }}</p>
                        <p class="text-xs text-surface-500 truncate">{{ $memberEmail }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <x-ui.badge variant="neutral">{{ $roleLabels[$member->role] ?? $member->role }}</x-ui.badge>
                    @if($member->isAccepted())
                        <x-ui.badge variant="success" icon="fa-check">Aceito</x-ui.badge>
                    @elseif($member->isUnclaimed())
                        <x-ui.badge variant="info" icon="fa-envelope">Aguardando cadastro</x-ui.badge>
                    @else
                        <x-ui.badge variant="warning" icon="fa-clock">Pendente</x-ui.badge>
                    @endif
                    <form autocomplete="off" method="POST" action="{{ route('wallets.share.destroy', $member) }}"
                        data-confirm="Remover o acesso de {{ $memberName }} a esta carteira? A pessoa pode ser convidada novamente depois.">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="p-2 rounded-lg text-surface-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/10 transition"
                            aria-label="Remover acesso" title="Remover acesso">
                            <i class="fas fa-user-minus"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            @if($canShare)
                <p class="text-sm text-surface-500 dark:text-surface-400 py-2">
                    Esta carteira ainda não foi compartilhada. Use <span class="font-medium">Convidar pessoa</span> para dar acesso a alguém.
                </p>
            @endif
        @endforelse
    </x-ui.section-card>
    @endif

    {{-- Últimos lançamentos --}}
    <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-surface-100 dark:border-surface-800 flex items-center justify-between">
            <h2 class="font-semibold text-surface-900 dark:text-white">Últimos lançamentos</h2>
            <a href="{{ route('transactions.index', ['wallet_id' => $wallet->id, 'month' => 'all', 'year' => now()->year]) }}" class="text-sm text-primary-600 hover:underline">Ver todos</a>
        </div>
        @forelse($recentTransactions as $transaction)
            @php
                // On a shared wallet, each entry still opens only for its author
                // (transaction detail is user-scoped). Render others as a plain row.
                $isMine = (string) $transaction->user_id === (string) auth()->id();
                $rowTag = $isMine ? 'a' : 'div';
            @endphp
            <{{ $rowTag }} @if($isMine) href="{{ route('transactions.show', $transaction) }}" @endif
                class="px-6 py-3 flex items-center justify-between gap-3 border-b last:border-0 border-surface-100 dark:border-surface-800 @if($isMine) hover:bg-surface-50 dark:hover:bg-surface-800/40 @endif">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-surface-900 dark:text-white truncate">{{ $transaction->description }}
                        @if($transaction->isInstallment())<span class="text-xs text-primary-600">{{ $transaction->installment_label }}</span>@endif
                    </p>
                    <p class="text-xs text-surface-500">
                        {{ $transaction->date->format('d/m/Y') }}{{ $transaction->category ? ' · ' . $transaction->category->name : '' }}
                        @if($wallet->is_shared && $transaction->user)
                            · <i class="fas fa-user text-[10px]"></i> {{ $isMine ? 'Você' : $transaction->user->name }}
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <x-tx.status-badge :transaction="$transaction" class="hidden sm:inline-flex" />
                    <span class="num text-sm font-semibold {{ $transaction->isIncome() ? 'text-primary-600 dark:text-primary-400' : 'text-red-600' }}">
                        {{ $transaction->isIncome() ? '+' : '-' }} R$ {{ number_format((float) $transaction->amount, 2, ',', '.') }}
                    </span>
                </div>
            </{{ $rowTag }}>
        @empty
            <p class="px-6 py-8 text-center text-sm text-surface-500">Nenhum lançamento nesta carteira ainda.</p>
        @endforelse
    </div>
</div>
@endsection
