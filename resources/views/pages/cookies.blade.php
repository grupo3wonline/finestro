@extends('layouts.landing')

@section('title', 'Política de Cookies')
@section('meta_description', 'Política de Cookies do Finestro: lista de cookies usados, finalidade, duração, como mudar suas preferências e como recusar a venda ou o compartilhamento de dados.')

@php
    $sessionMinutes = (int) config('session.lifetime');
    $hasAnalytics = filled(config('services.google.analytics_id')) || filled(config('services.google.gtm_id'));
    $hasMarketing = filled(config('services.facebook.pixel_id'));
    $h2 = 'text-xl font-semibold text-surface-900 dark:text-white scroll-mt-24';
    $link = 'text-primary-600 dark:text-primary-400 hover:underline';
    $cookies = [
        ['name' => (string) config('session.cookie'), 'category' => 'Necessário', 'purpose' => 'Mantém sua sessão de login e as mensagens entre páginas.', 'duration' => $sessionMinutes.' minutos sem atividade'],
        ['name' => 'XSRF-TOKEN', 'category' => 'Necessário', 'purpose' => 'Protege formulários contra envio forjado por outros sites (CSRF).', 'duration' => $sessionMinutes.' minutos'],
        ['name' => 'remember_web_*', 'category' => 'Necessário', 'purpose' => 'Só quando você marca "Lembrar de mim": mantém você conectado neste navegador.', 'duration' => 'até 400 dias ou até você sair'],
        ['name' => (string) config('privacy.consent_cookie'), 'category' => 'Necessário', 'purpose' => 'Guarda suas escolhas de cookies (versão da política, análise, marketing e data).', 'duration' => (int) config('privacy.consent_lifetime_days').' dias'],
        ['name' => 'darkMode (armazenamento local)', 'category' => 'Necessário', 'purpose' => 'Lembra se você prefere o tema claro ou escuro. Fica só no seu navegador.', 'duration' => 'até você limpar os dados do navegador'],
    ];
    if ($hasAnalytics) {
        $cookies[] = ['name' => '_ga, _ga_*', 'category' => 'Análise', 'purpose' => 'Google Analytics: conta visitas e páginas vistas de forma estatística. Só é criado se você permitir análise.', 'duration' => '2 anos'];
    }
    if ($hasMarketing) {
        $cookies[] = ['name' => '_fbp', 'category' => 'Marketing', 'purpose' => 'Meta Pixel: mede campanhas e anúncios. Só é criado se você permitir marketing.', 'duration' => '90 dias'];
    }
@endphp

@section('content')
<section class="py-12 sm:py-16 bg-surface-50 dark:bg-surface-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <x-ui.breadcrumb :items="[['label' => 'Política de Privacidade', 'url' => route('privacy')], ['label' => 'Política de Cookies']]" />
        </div>

        <article class="bg-white dark:bg-surface-950 rounded-2xl p-6 sm:p-8 md:p-12 shadow-sm border border-surface-100 dark:border-surface-800">
            <h1 class="text-3xl font-bold text-surface-900 dark:text-white mb-3">Política de Cookies</h1>
            <p class="text-sm text-surface-500 dark:text-surface-400 mb-8">Versão {{ config('legal.policy_version') }}</p>

            <div class="space-y-6 text-surface-700 dark:text-surface-300 leading-relaxed">
                <p>
                    Cookies são pequenos arquivos que o site guarda no seu navegador. Usamos apenas os necessários para o Finestro funcionar
                    e, somente com a sua permissão, cookies de análise e de marketing. Nenhum script de análise ou anúncio é carregado antes da sua escolha.
                </p>

                <x-ui.tip icon="fa-lightbulb">
                    Dica: você pode mudar de ideia quando quiser pelo botão "Preferências de cookies" no rodapé ou abaixo. Rejeitar os cookies opcionais não limita nenhum recurso do Finestro.
                </x-ui.tip>

                <h2 id="lista" class="{{ $h2 }}">Cookies que usamos</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border border-surface-200 dark:border-surface-800">
                        <caption class="sr-only">Cookies e armazenamento local usados pelo Finestro</caption>
                        <thead class="bg-surface-50 dark:bg-surface-900">
                            <tr class="text-left">
                                <th scope="col" class="p-2 font-semibold">Nome</th>
                                <th scope="col" class="p-2 font-semibold">Categoria</th>
                                <th scope="col" class="p-2 font-semibold">Para que serve</th>
                                <th scope="col" class="p-2 font-semibold">Duração</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-200 dark:divide-surface-800">
                            @foreach($cookies as $cookie)
                                <tr>
                                    <td class="p-2 font-mono text-xs break-all">{{ $cookie['name'] }}</td>
                                    <td class="p-2">{{ $cookie['category'] }}</td>
                                    <td class="p-2">{{ $cookie['purpose'] }}</td>
                                    <td class="p-2 whitespace-nowrap">{{ $cookie['duration'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @unless($hasAnalytics || $hasMarketing)
                    <p class="text-sm">No momento não usamos cookies de análise nem de marketing. Se passarmos a usar, eles aparecerão nesta lista e só serão ativados com a sua permissão.</p>
                @endunless

                <h2 id="preferencias" class="{{ $h2 }}">Como mudar suas preferências</h2>
                <p>
                    Clique em <button type="button" @click="$dispatch('open-cookie-preferences')" class="{{ $link }} font-medium">Preferências de cookies</button>
                    para ativar ou desativar análise e marketing. Você também pode apagar cookies nas configurações do seu navegador.
                    @auth Com login, suas escolhas também ficam registradas em <a href="{{ route('settings.privacy') }}" class="{{ $link }}">Configurações &gt; Privacidade</a>. @endauth
                </p>

                <h2 id="nao-vender" class="{{ $h2 }}">Não venda ou compartilhe minhas informações</h2>
                <p>
                    Não vendemos nem compartilhamos dados pessoais no sentido da CCPA/CPRA. Ainda assim, cookies de marketing enviam dados de navegação
                    a plataformas de anúncios. Use o botão abaixo para desligá-los neste navegador; sua escolha de cookies de análise é mantida.
                    Respeitamos também o sinal Global Privacy Control (GPC).
                </p>
                @if($gpc)
                    <x-ui.alert variant="info">Detectamos o sinal Global Privacy Control do seu navegador: cookies de marketing ficam desativados.</x-ui.alert>
                @endif
                <form autocomplete="off" action="{{ route('consent.store') }}" method="POST" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <input type="hidden" name="analytics" value="{{ $currentConsent->analytics ? 1 : 0 }}">
                    <input type="hidden" name="marketing" value="0">
                    <input type="hidden" name="source" value="settings">
                    <x-ui.button type="submit" variant="primary" icon="fa-ban" title="Desliga os cookies de marketing neste navegador">
                        Não venda ou compartilhe minhas informações
                    </x-ui.button>
                    <x-ui.button variant="outline" icon="fa-sliders" @click="$dispatch('open-cookie-preferences')" title="Abrir as preferências de cookies">
                        Preferências de cookies
                    </x-ui.button>
                </form>
                @if($currentConsent->isDecided() && ! $currentConsent->marketing)
                    <p class="text-sm"><i class="fas fa-circle-check text-primary-600 mr-1" aria-hidden="true"></i>Cookies de marketing estão desligados neste navegador.</p>
                @endif

                <p>Mais detalhes sobre o tratamento de dados estão na <a href="{{ route('privacy') }}" class="{{ $link }}">Política de Privacidade</a>.</p>
            </div>
        </article>
    </div>
</section>
@endsection
