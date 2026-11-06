@extends('layouts.app')

@section('title', 'Tokens de API')

@section('breadcrumb')
    <x-ui.breadcrumb :items="[['label' => 'Configurações', 'url' => route('settings.index')], ['label' => 'Tokens de API']]" />
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <x-ui.page-header title="Tokens de API" subtitle="Conecte planilhas, automações e outros sistemas aos seus dados do Finestro com segurança." icon="fa-key">
        <x-slot:actions>
            <x-ui.button :href="route('settings.index')" variant="outline" size="sm" icon="fa-arrow-left" title="Voltar para Configurações">
                Configurações
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.tip icon="fa-circle-info">
        Envie o token no cabeçalho <code class="font-mono text-xs">Authorization: Bearer SEU_TOKEN</code> de cada requisição.
        Exemplo: <code class="font-mono text-xs break-all">curl -H "Authorization: Bearer SEU_TOKEN" -H "Accept: application/json" {{ url('/api/v1/auth/me') }}</code>.
        Dê a cada integração apenas as permissões de que ela precisa e revogue tokens que não usa mais.
        Os tokens expiram automaticamente após {{ intdiv((int) config('sanctum.expiration'), 1440) }} dias.
        @if($showDocsLink)
            Consulte a <a href="{{ url('/api/documentation') }}" class="font-medium text-primary-600 dark:text-primary-400 hover:underline">documentação da API</a>.
        @endif
    </x-ui.tip>

    @if(session('plain_token'))
        <div x-data="{
                token: @js(session('plain_token')),
                copy() {
                    navigator.clipboard.writeText(this.token).then(() => {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Token copiado!', showConfirmButton: false, timer: 2000, timerProgressBar: true });
                    });
                }
            }">
            <x-ui.alert variant="warning" title="Copie seu novo token agora">
                <p class="text-sm mb-3">Por segurança, este token não será exibido novamente. Guarde-o em um local seguro, como um gerenciador de senhas.</p>
                <div class="flex flex-col sm:flex-row gap-2">
                    <label for="plain_token" class="sr-only">Seu novo token de API</label>
                    <input type="text" id="plain_token" readonly autocomplete="off" x-bind:value="token" value="{{ session('plain_token') }}"
                        class="input font-mono text-xs flex-1" @focus="$event.target.select()">
                    <x-ui.button variant="primary" size="sm" icon="fa-copy" @click="copy()" title="Copiar o token para a área de transferência">
                        Copiar
                    </x-ui.button>
                </div>
            </x-ui.alert>
        </div>
    @endif

    @if($canCreate)
        <x-ui.section-card title="Criar novo token" icon="fa-plus">
            <form action="{{ route('settings.tokens.store') }}" method="POST" autocomplete="off" class="space-y-5">
                @csrf

                <x-ui.field label="Nome do token" for="name" error="name" required tooltip="Um nome que ajude você a lembrar onde o token é usado." hint="Ex.: Planilha Google, Automação Zapier.">
                    <x-ui.input name="name" icon="fa-tag" maxlength="60" required :value="old('name')" error="name" placeholder="Ex.: Planilha Google" />
                </x-ui.field>

                <fieldset>
                    <legend class="input-label flex items-center gap-1">
                        Permissões <span class="text-red-500">*</span>
                        <span class="text-surface-400 dark:text-surface-500 cursor-help" title="Leitura permite consultar dados. Escrita permite criar, alterar e excluir.">
                            <i class="fas fa-circle-question text-xs"></i>
                        </span>
                    </legend>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($abilityGroups as $resource => $abilities)
                            <div class="rounded-xl border border-surface-100 dark:border-surface-800 bg-surface-50 dark:bg-surface-800/60 px-4 py-3">
                                <p class="text-sm font-medium text-surface-800 dark:text-surface-200 mb-2">{{ $resource }}</p>
                                <div class="flex flex-wrap gap-x-5 gap-y-2">
                                    @foreach($abilities as $ability)
                                        <label class="inline-flex items-center gap-2 text-sm text-surface-700 dark:text-surface-300 cursor-pointer min-h-[40px]">
                                            <input type="checkbox" name="abilities[]" value="{{ $ability->value }}" autocomplete="off"
                                                @checked(in_array($ability->value, (array) old('abilities', []), true))
                                                class="rounded border-surface-300 dark:border-surface-600 text-primary-600 focus:ring-primary-500">
                                            {{ $ability->isWrite() ? 'Escrita' : 'Leitura' }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('abilities') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    @error('abilities.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </fieldset>

                <x-ui.button type="submit" variant="primary" icon="fa-key" loading="Criando token...">
                    Criar token
                </x-ui.button>
            </form>
        </x-ui.section-card>
    @else
        <x-ui.section-card title="API disponível no plano Profissional" icon="fa-lock" iconColor="accent">
            <x-ui.empty-state icon="fa-plug" title="Integre o Finestro com outras ferramentas"
                text="Com o plano Profissional você cria tokens de API para conectar planilhas, automações e sistemas aos seus dados, com permissões sob medida.">
                <x-slot:action>
                    <x-ui.button variant="accent" :href="route('subscription.plans')" icon="fa-crown" title="Conhecer o plano Profissional">
                        Fazer upgrade para o Profissional
                    </x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        </x-ui.section-card>
    @endif

    @if($canCreate || $tokens->isNotEmpty())
        <x-ui.section-card title="Seus tokens" icon="fa-list" bodyClass="p-0">
            @if($tokens->isEmpty())
                <x-ui.empty-state icon="fa-key" title="Nenhum token criado" text="Crie um token acima para começar a usar a API." />
            @else
                <ul class="divide-y divide-surface-100 dark:divide-surface-800">
                    @foreach($tokens as $token)
                        <li class="px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="min-w-0 space-y-2">
                                <p class="font-medium text-surface-900 dark:text-white break-words">
                                    <i class="fas fa-key text-primary-500 mr-1"></i>{{ $token->name }}
                                </p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach((array) $token->abilities as $ability)
                                        <x-ui.badge :variant="str_ends_with($ability, ':write') ? 'warning' : 'info'">
                                            {{ $abilityLabels[$ability] ?? ($ability === '*' ? 'Acesso total' : $ability) }}
                                        </x-ui.badge>
                                    @endforeach
                                </div>
                                <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-surface-500 dark:text-surface-400">
                                    <span title="Última vez que o token foi usado"><i class="fas fa-clock mr-1"></i>{{ $token->last_used_at ? 'Último uso: '.$token->last_used_at->timezone(config('app.timezone'))->format('d/m/Y H:i') : 'Nunca usado' }}</span>
                                    <span><i class="fas fa-calendar-plus mr-1"></i>Criado em {{ $token->created_at?->timezone(config('app.timezone'))->format('d/m/Y') }}</span>
                                    <span><i class="fas fa-hourglass-end mr-1"></i>{{ $token->expires_at ? 'Expira em '.$token->expires_at->timezone(config('app.timezone'))->format('d/m/Y') : 'Sem expiração' }}</span>
                                </div>
                            </div>
                            <form autocomplete="off" action="{{ route('settings.tokens.destroy', $token->id) }}" method="POST" class="shrink-0"
                                data-confirm="Revogar o token &quot;{{ $token->name }}&quot;? Aplicações que usam este token perderão o acesso imediatamente.">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="danger" size="sm" icon="fa-ban" title="Revogar este token">
                                    Revogar
                                </x-ui.button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.section-card>
    @endif
</div>
@endsection
