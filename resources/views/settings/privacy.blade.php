@extends('layouts.app')

@section('title', 'Privacidade')

@section('breadcrumb')
    <x-ui.breadcrumb :items="[['label' => 'Configurações', 'url' => route('settings.index')], ['label' => 'Privacidade']]" />
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <x-ui.page-header title="Privacidade e seus dados" subtitle="Exporte seus dados, revise seus consentimentos e, se quiser, exclua sua conta." icon="fa-user-shield">
        <x-slot:actions>
            <x-ui.button :href="route('settings.index')" variant="outline" size="sm" icon="fa-arrow-left" title="Voltar para Configurações">
                Configurações
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.tip icon="fa-scale-balanced">
        Pela LGPD (art. 18), GDPR (arts. 15 a 22), CCPA/CPRA e POPIA você pode acessar, corrigir, portar e excluir seus dados e revogar consentimentos.
        Quase tudo pode ser feito aqui mesmo. Para correções de cadastro use <a href="{{ route('profile.edit') }}" class="font-medium text-primary-600 dark:text-primary-400 hover:underline">Editar perfil</a>;
        para outros pedidos, escreva ao encarregado de dados: <a href="mailto:{{ $dpoEmail }}" class="font-medium text-primary-600 dark:text-primary-400 hover:underline">{{ $dpoEmail }}</a>.
        Detalhes na <a href="{{ route('privacy') }}#seus-direitos" class="font-medium text-primary-600 dark:text-primary-400 hover:underline">Política de Privacidade</a>.
    </x-ui.tip>

    {{-- Data export (access + portability) --}}
    <x-ui.section-card title="Exportar meus dados" icon="fa-file-zipper">
        <p class="text-sm text-surface-600 dark:text-surface-400">
            Gere um arquivo .zip com tudo o que guardamos sobre você: <strong>data.json</strong> (formato legível por máquinas, para levar a outro serviço)
            e uma planilha <strong>.csv</strong> por assunto, que abre no Excel ou no Google Planilhas.
        </p>
        <ul class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 text-sm text-surface-600 dark:text-surface-400 list-disc pl-5">
            <li>Dados da conta (nome, usuário, e-mail, aceite dos termos)</li>
            <li>Carteiras, transações, categorias, metas e orçamentos</li>
            <li>Assinatura e histórico de pagamentos</li>
            <li>Comprovantes: nome, tipo e tamanho de cada arquivo</li>
            <li>Histórico de consentimento de cookies</li>
            <li>Registro de atividade (evento e data)</li>
        </ul>
        <p class="mt-3 text-xs text-surface-500 dark:text-surface-400">
            Exemplo: <code class="font-mono">transactions.csv</code> traz uma linha por transação com descrição, valor, data e situação.
            Senhas, tokens e chaves de IA nunca entram no arquivo.
        </p>

        <form autocomplete="off" action="{{ route('settings.privacy.export') }}" method="POST" class="mt-4" data-loading="Preparando sua exportação...">
            @csrf
            <x-ui.button type="submit" variant="primary" size="sm" icon="fa-download" :disabled="$hasPendingExport"
                title="Gera um novo arquivo com seus dados">
                {{ $hasPendingExport ? 'Exportação em preparação' : 'Solicitar exportação' }}
            </x-ui.button>
            <p class="mt-2 text-xs text-surface-500 dark:text-surface-400">
                O link de download vale por {{ $exportTtlHours }} horas e só funciona na sua conta. Depois disso o arquivo é apagado automaticamente.
                Limite: 3 solicitações por hora.
            </p>
        </form>

        @if($exports->isNotEmpty())
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <caption class="sr-only">Exportações solicitadas</caption>
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-surface-500 dark:text-surface-400 border-b border-surface-100 dark:border-surface-800">
                            <th scope="col" class="py-2 pr-4 font-medium">Solicitada em</th>
                            <th scope="col" class="py-2 pr-4 font-medium">Situação</th>
                            <th scope="col" class="py-2 pr-4 font-medium">Tamanho</th>
                            <th scope="col" class="py-2 pr-4 font-medium">Disponível até</th>
                            <th scope="col" class="py-2 font-medium"><span class="sr-only">Ação</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                        @foreach($exports as $row)
                            @php($export = $row['export'])
                            @php($status = $export->isExpired() ? \App\Enums\DataExportStatus::Expired : ($export->isStale() ? \App\Enums\DataExportStatus::Failed : $export->status))
                            <tr>
                                <td class="py-2.5 pr-4 text-surface-700 dark:text-surface-300 whitespace-nowrap">{{ $export->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-2.5 pr-4"><x-ui.badge :variant="$status->badgeVariant()">{{ $status->label() }}</x-ui.badge></td>
                                <td class="py-2.5 pr-4 text-surface-700 dark:text-surface-300 whitespace-nowrap">{{ $export->size ? \Illuminate\Support\Number::fileSize($export->size, 1) : '—' }}</td>
                                <td class="py-2.5 pr-4 text-surface-700 dark:text-surface-300 whitespace-nowrap">{{ $export->expires_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="py-2.5 text-right">
                                    @if($row['url'])
                                        <a href="{{ $row['url'] }}" class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline" data-no-loading title="Baixar o arquivo .zip com seus dados">
                                            <i class="fas fa-download" aria-hidden="true"></i> Baixar
                                        </a>
                                    @elseif($status === \App\Enums\DataExportStatus::Failed)
                                        <span class="text-xs text-surface-500 dark:text-surface-400">Tente solicitar novamente</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($hasPendingExport)
                <p class="mt-2 text-xs text-surface-500 dark:text-surface-400"><i class="fas fa-circle-info mr-1" aria-hidden="true"></i>Atualize a página em alguns minutos para ver o link de download.</p>
            @endif
        @endif
    </x-ui.section-card>

    {{-- Cookie / marketing preferences --}}
    <x-ui.section-card title="Preferências de cookies e marketing" icon="fa-cookie-bite">
        <p class="text-sm text-surface-600 dark:text-surface-400 mb-4">
            Cookies necessários (sessão e segurança) estão sempre ativos. Os demais só funcionam com a sua permissão, e a escolha vale para este navegador.
            Não vendemos nem compartilhamos seus dados pessoais.
            @unless($currentConsent->isDecided())
                <strong>Você ainda não fez uma escolha neste navegador</strong>, então só os cookies necessários estão em uso.
            @endunless
        </p>

        @if($gpc)
            <x-ui.alert variant="info" class="mb-4">
                Detectamos o sinal Global Privacy Control do seu navegador: cookies de marketing ficam desativados.
            </x-ui.alert>
        @endif

        <form autocomplete="off" action="{{ route('consent.store') }}" method="POST" class="space-y-3">
            @csrf
            <input type="hidden" name="source" value="settings">

            <input type="hidden" name="analytics" value="0">
            <label for="privacy-analytics" class="flex items-start justify-between gap-4 p-3 rounded-xl bg-surface-50 dark:bg-surface-800/60 cursor-pointer">
                <span>
                    <span class="block text-sm font-semibold text-surface-900 dark:text-white">Análise</span>
                    <span class="block text-xs text-surface-600 dark:text-surface-400">Estatísticas de uso (Google Analytics / Tag Manager) para melhorarmos o Finestro.</span>
                </span>
                <input type="checkbox" id="privacy-analytics" name="analytics" value="1" autocomplete="off" @checked($currentConsent->analytics)
                    class="mt-1 h-5 w-5 rounded border-surface-300 text-primary-600 focus:ring-primary-500">
            </label>

            <input type="hidden" name="marketing" value="0">
            <label for="privacy-marketing" class="flex items-start justify-between gap-4 p-3 rounded-xl bg-surface-50 dark:bg-surface-800/60 {{ $gpc ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer' }}">
                <span>
                    <span class="block text-sm font-semibold text-surface-900 dark:text-white">Marketing</span>
                    <span class="block text-xs text-surface-600 dark:text-surface-400">Medição de campanhas e anúncios (Meta Pixel, Google Ads). Desmarque para não ter seus dados usados em anúncios.</span>
                </span>
                <input type="checkbox" id="privacy-marketing" name="marketing" value="1" autocomplete="off" @checked($currentConsent->marketing) @disabled($gpc)
                    class="mt-1 h-5 w-5 rounded border-surface-300 text-primary-600 focus:ring-primary-500">
            </label>

            <div class="flex flex-wrap items-center gap-2 pt-1">
                <x-ui.button type="submit" variant="primary" size="sm" icon="fa-floppy-disk" title="Salvar suas escolhas de cookies">
                    Salvar preferências
                </x-ui.button>
                <x-ui.button :href="route('cookies')" variant="ghost" size="sm" icon="fa-circle-question" title="Ver a lista de cookies usados">
                    Quais cookies usamos?
                </x-ui.button>
            </div>
        </form>
    </x-ui.section-card>

    {{-- Consent history (proof of consent) --}}
    <x-ui.section-card title="Histórico de consentimento" icon="fa-clock-rotate-left">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm mb-4">
            <div>
                <dt class="text-surface-500 dark:text-surface-400">Termos de Uso e Política de Privacidade</dt>
                <dd class="font-medium text-surface-900 dark:text-white">
                    @if($user->terms_accepted_at)
                        Versão {{ $user->terms_version ?? '—' }}, aceita em {{ $user->terms_accepted_at->format('d/m/Y H:i') }}
                    @else
                        Ainda não aceitos
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-surface-500 dark:text-surface-400">Versão atual dos documentos</dt>
                <dd class="font-medium text-surface-900 dark:text-white">{{ config('legal.policy_version') }}</dd>
            </div>
        </dl>

        @if($consentHistory->isEmpty())
            <p class="text-sm text-surface-500 dark:text-surface-400">Nenhuma escolha de cookies registrada enquanto você estava conectado.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <caption class="sr-only">Escolhas de cookies registradas</caption>
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-surface-500 dark:text-surface-400 border-b border-surface-100 dark:border-surface-800">
                            <th scope="col" class="py-2 pr-4 font-medium">Data</th>
                            <th scope="col" class="py-2 pr-4 font-medium">Versão</th>
                            <th scope="col" class="py-2 pr-4 font-medium">Análise</th>
                            <th scope="col" class="py-2 pr-4 font-medium">Marketing</th>
                            <th scope="col" class="py-2 font-medium">Origem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                        @foreach($consentHistory as $consent)
                            <tr>
                                <td class="py-2.5 pr-4 text-surface-700 dark:text-surface-300 whitespace-nowrap">{{ $consent->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-2.5 pr-4 text-surface-700 dark:text-surface-300">{{ $consent->version }}</td>
                                <td class="py-2.5 pr-4"><x-ui.badge :variant="$consent->analytics ? 'success' : 'neutral'">{{ $consent->analytics ? 'Permitido' : 'Recusado' }}</x-ui.badge></td>
                                <td class="py-2.5 pr-4">
                                    <x-ui.badge :variant="$consent->marketing ? 'success' : 'neutral'">{{ $consent->marketing ? 'Permitido' : 'Recusado' }}</x-ui.badge>
                                    @if($consent->gpc)<span class="ml-1 text-xs text-surface-500 dark:text-surface-400" title="Sinal Global Privacy Control do navegador">(GPC)</span>@endif
                                </td>
                                <td class="py-2.5 text-surface-700 dark:text-surface-300">{{ $consent->source === \App\Models\CookieConsent::SOURCE_SETTINGS ? 'Configurações' : 'Aviso de cookies' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.section-card>

    {{-- Account deletion --}}
    <x-ui.section-card title="Excluir minha conta" icon="fa-triangle-exclamation" iconColor="red" class="border-red-200 dark:border-red-900">
        <p class="text-sm text-surface-600 dark:text-surface-400">O que acontece ao excluir a conta:</p>
        <ul class="mt-2 space-y-1 text-sm text-surface-600 dark:text-surface-400 list-disc pl-5">
            <li>Seu acesso termina na hora e você sai de todos os dispositivos; tokens de API são revogados.</li>
            <li>Se você tem o plano Profissional, a assinatura é cancelada no Stripe e não há novas cobranças.</li>
            <li>Seus dados são desativados e deixam de aparecer no Finestro.</li>
            <li>A eliminação definitiva dos dados pode ser pedida ao encarregado pelo e-mail <a href="mailto:{{ $dpoEmail }}" class="font-medium text-primary-600 dark:text-primary-400 hover:underline">{{ $dpoEmail }}</a>, salvo o que a lei obriga a guardar.</li>
        </ul>
        <x-ui.tip icon="fa-lightbulb" class="mt-4">
            Antes de excluir, use "Exportar meus dados" acima para guardar uma cópia. Depois da exclusão não é possível entrar na conta para baixá-la.
        </x-ui.tip>

        <form autocomplete="off" action="{{ route('settings.account.delete') }}" method="POST" class="mt-4 space-y-3"
            data-confirm="Excluir sua conta agora? Seu acesso termina imediatamente e a assinatura (se houver) é cancelada.">
            @csrf
            @method('DELETE')
            <x-ui.field label="Confirme sua senha" for="password" error="password" required tooltip="Por segurança, pedimos sua senha atual antes de excluir a conta.">
                <x-ui.input type="password" name="password" icon="fa-lock" required autocomplete="off" error="password" placeholder="Sua senha atual" />
            </x-ui.field>
            <x-ui.button type="submit" variant="danger" size="sm" icon="fa-trash" title="Excluir sua conta do Finestro">
                Excluir minha conta
            </x-ui.button>
        </form>
    </x-ui.section-card>
</div>
@endsection
