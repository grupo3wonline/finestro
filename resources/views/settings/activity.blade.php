@extends('layouts.app')

@section('title', 'Atividade da conta')

@section('breadcrumb')
    <x-ui.breadcrumb :items="[['label' => 'Configurações', 'url' => route('settings.index')], ['label' => 'Atividade da conta']]" />
@endsection

@php
    $retentionDays = (int) config('activitylog.clean_after_days');

    $eventVariants = [
        'created' => 'success',
        'updated' => 'info',
        'deleted' => 'danger',
        'restored' => 'info',
        'login' => 'success',
        'logout' => 'neutral',
        'login_failed' => 'danger',
        'password_reset' => 'warning',
        'password_changed' => 'warning',
        'registered' => 'success',
        'role_attached' => 'info',
        'role_detached' => 'warning',
        'account_deleted' => 'danger',
        'api_token_created' => 'info',
        'api_token_revoked' => 'warning',
    ];

    $eventIcons = [
        'created' => 'fa-plus',
        'updated' => 'fa-pen',
        'deleted' => 'fa-trash',
        'restored' => 'fa-rotate-left',
        'login' => 'fa-right-to-bracket',
        'logout' => 'fa-right-from-bracket',
        'login_failed' => 'fa-triangle-exclamation',
        'password_reset' => 'fa-key',
        'password_changed' => 'fa-key',
        'registered' => 'fa-user-plus',
        'role_attached' => 'fa-user-shield',
        'role_detached' => 'fa-user-shield',
        'account_deleted' => 'fa-user-xmark',
        'api_token_created' => 'fa-key',
        'api_token_revoked' => 'fa-key',
    ];
@endphp

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <x-ui.page-header title="Atividade da conta" subtitle="Acompanhe acessos e alterações feitas na sua conta, do mais recente para o mais antigo." icon="fa-clock-rotate-left">
        <x-slot:actions>
            <x-ui.button :href="route('settings.index')" variant="outline" size="sm" icon="fa-arrow-left" title="Voltar para Configurações">
                Configurações
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.tip icon="fa-shield-halved">
        Registramos alterações em carteiras, transações, metas, orçamentos, categorias e assinatura,
        além de entradas e saídas da conta, tentativas de acesso sem sucesso e o endereço IP de cada acesso.
        Nunca registramos senhas, chaves de API ou dados de cartão.
        Os registros ficam disponíveis por {{ $retentionDays }} dias e depois são apagados automaticamente.
        Viu algo que não reconhece? Altere sua senha em Configurações.
    </x-ui.tip>

    <x-ui.section-card title="Histórico" icon="fa-list-check" bodyClass="p-0">
        @if($entries->isEmpty())
            <x-ui.empty-state icon="fa-clock-rotate-left" title="Nenhuma atividade registrada" text="Assim que você usar o Finestro, seus acessos e alterações aparecerão aqui." />
        @else
            <ul class="divide-y divide-surface-100 dark:divide-surface-800">
                @foreach($entries as $entry)
                    <li class="px-4 sm:px-6 py-4">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2 min-w-0">
                                <x-ui.badge :variant="$eventVariants[$entry->event] ?? 'neutral'" :icon="$eventIcons[$entry->event] ?? 'fa-circle-info'">
                                    {{ $entry->eventLabel }}
                                </x-ui.badge>
                                <span class="text-sm font-medium text-surface-900 dark:text-white">{{ $entry->subjectLabel }}</span>
                                @if($entry->viaSystem)
                                    <x-ui.badge variant="neutral" icon="fa-robot" title="Alteração feita automaticamente pelo sistema (ex.: recorrências ou atualização da assinatura)">via sistema</x-ui.badge>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-surface-500 dark:text-surface-400 shrink-0">
                                <span><i class="fas fa-calendar-day mr-1"></i>{{ $entry->date }}</span>
                                @if($entry->ip)
                                    <span title="Endereço IP de onde a ação foi feita"><i class="fas fa-network-wired mr-1"></i>IP {{ $entry->ip }}</span>
                                @endif
                            </div>
                        </div>

                        @if(count($entry->changes) > 0)
                            <dl class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach($entry->changes as $change)
                                    <div class="rounded-xl bg-surface-50 dark:bg-surface-800/60 px-3 py-2 text-sm">
                                        <dt class="text-xs font-medium text-surface-500 dark:text-surface-400">{{ $change['field'] }}</dt>
                                        <dd class="mt-0.5 text-surface-800 dark:text-surface-200 break-words">
                                            @if($change['old'] !== null)
                                                <span class="line-through text-surface-400 dark:text-surface-500">{{ $change['old'] }}</span>
                                                <i class="fas fa-arrow-right text-[10px] mx-1 text-surface-400" aria-label="para"></i>
                                            @endif
                                            <span>{{ $change['new'] ?? '—' }}</span>
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.section-card>

    @if($entries->hasPages())
        <div>{{ $entries->links() }}</div>
    @endif
</div>
@endsection
