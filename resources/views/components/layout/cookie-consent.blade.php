{{-- Cookie consent banner (LGPD / GDPR / CCPA-CPRA / POPIA). --}}
{{-- Opt-in per category; rejecting is as easy as accepting; reopened by the --}}
{{-- "Preferências de cookies" button (event open-cookie-preferences). Logic: resources/js/consent.js --}}
<div x-data="cookieConsent" x-show="show" x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    @keydown.escape.window="if (customizing) customizing = false"
    class="fixed inset-x-0 bottom-0 z-[60] p-4 sm:p-6"
    role="dialog" aria-modal="false" aria-labelledby="cookie-consent-title" aria-describedby="cookie-consent-text">
    <div class="max-w-4xl mx-auto bg-white dark:bg-surface-800 rounded-2xl shadow-elevated border border-surface-200 dark:border-surface-700 p-5 sm:p-6 max-h-[85vh] overflow-y-auto">
        <div class="flex items-center gap-2 mb-2">
            <i class="fas fa-cookie-bite text-primary-600 dark:text-primary-400" aria-hidden="true"></i>
            <h2 id="cookie-consent-title" class="text-base font-semibold text-surface-900 dark:text-white">Sua privacidade, suas escolhas</h2>
        </div>
        <p id="cookie-consent-text" class="text-sm text-surface-600 dark:text-surface-300 leading-relaxed">
            Usamos cookies necessários para o site funcionar (sessão, segurança e esta escolha).
            Cookies de análise e de marketing são opcionais e só são ativados se você permitir.
            Você pode mudar de ideia quando quiser em "Preferências de cookies", no rodapé.
            Saiba mais na <a href="{{ route('cookies') }}" class="text-primary-600 dark:text-primary-400 underline hover:no-underline">Política de Cookies</a>
            e na <a href="{{ route('privacy') }}" class="text-primary-600 dark:text-primary-400 underline hover:no-underline">Política de Privacidade</a>.
        </p>

        <template x-if="gpc">
            <p class="mt-3 text-xs text-surface-600 dark:text-surface-300 flex items-start gap-2">
                <i class="fas fa-shield-halved text-primary-600 dark:text-primary-400 mt-0.5" aria-hidden="true"></i>
                <span>Detectamos o sinal Global Privacy Control do seu navegador: cookies de marketing ficam desativados.</span>
            </p>
        </template>

        {{-- Granular choices --}}
        <div x-show="customizing" x-cloak class="mt-4 space-y-3" id="cookie-consent-options">
            <div class="flex items-start justify-between gap-4 p-3 rounded-xl bg-surface-50 dark:bg-surface-900/60">
                <div>
                    <p class="text-sm font-semibold text-surface-900 dark:text-white">Necessários <span class="text-xs font-normal text-surface-500 dark:text-surface-400">(sempre ativos)</span></p>
                    <p class="text-xs text-surface-600 dark:text-surface-400">Sessão de login, proteção contra ataques (CSRF) e o registro desta escolha. Sem eles o site não funciona.</p>
                </div>
                <input type="checkbox" checked disabled autocomplete="off" aria-label="Cookies necessários (sempre ativos)"
                    class="mt-1 h-5 w-5 rounded border-surface-300 text-primary-600 opacity-60 cursor-not-allowed">
            </div>
            <label for="cookie-consent-analytics" class="flex items-start justify-between gap-4 p-3 rounded-xl bg-surface-50 dark:bg-surface-900/60 cursor-pointer">
                <span>
                    <span class="block text-sm font-semibold text-surface-900 dark:text-white">Análise</span>
                    <span class="block text-xs text-surface-600 dark:text-surface-400">Estatísticas anônimas de visitas (Google Analytics / Tag Manager) para entendermos o que melhorar. Ex.: quais páginas são mais acessadas.</span>
                </span>
                <input type="checkbox" id="cookie-consent-analytics" x-model="analytics" autocomplete="off"
                    class="mt-1 h-5 w-5 rounded border-surface-300 text-primary-600 focus:ring-primary-500">
            </label>
            <label for="cookie-consent-marketing" class="flex items-start justify-between gap-4 p-3 rounded-xl bg-surface-50 dark:bg-surface-900/60" :class="gpc ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer'">
                <span>
                    <span class="block text-sm font-semibold text-surface-900 dark:text-white">Marketing</span>
                    <span class="block text-xs text-surface-600 dark:text-surface-400">Medição de campanhas e anúncios (Meta Pixel, Google Ads). Desligado, você continua vendo anúncios, mas sem personalização baseada neste site.</span>
                </span>
                <input type="checkbox" id="cookie-consent-marketing" x-model="marketing" :disabled="gpc" autocomplete="off"
                    class="mt-1 h-5 w-5 rounded border-surface-300 text-primary-600 focus:ring-primary-500">
            </label>
        </div>

        <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-2">
            <button type="button" @click="customizing = !customizing" :aria-expanded="customizing.toString()" aria-controls="cookie-consent-options"
                class="sm:mr-auto px-3 py-2.5 text-sm font-medium text-primary-700 dark:text-primary-400 hover:underline rounded-lg focus-visible:ring-2 focus-visible:ring-primary-500">
                <i class="fas fa-sliders mr-1" aria-hidden="true"></i>
                <span x-text="customizing ? 'Ocultar opções' : 'Personalizar'">Personalizar</span>
            </button>
            <button type="button" x-show="customizing" x-cloak @click="save()" :disabled="saving"
                class="w-full sm:w-auto sm:min-w-[11rem] px-4 py-2.5 text-sm font-semibold rounded-lg border-2 border-primary-600 text-primary-700 dark:text-primary-300 hover:bg-primary-50 dark:hover:bg-primary-900/30 transition focus-visible:ring-2 focus-visible:ring-primary-500">
                Salvar escolhas
            </button>
            {{-- Equal size and weight: rejecting is as easy as accepting. --}}
            <button type="button" @click="rejectAll()" :disabled="saving"
                class="w-full sm:w-auto sm:min-w-[11rem] px-4 py-2.5 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white transition focus-visible:ring-2 focus-visible:ring-primary-500">
                Rejeitar não essenciais
            </button>
            <button type="button" @click="acceptAll()" :disabled="saving"
                class="w-full sm:w-auto sm:min-w-[11rem] px-4 py-2.5 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white transition focus-visible:ring-2 focus-visible:ring-primary-500">
                Aceitar todos
            </button>
        </div>
    </div>
</div>
