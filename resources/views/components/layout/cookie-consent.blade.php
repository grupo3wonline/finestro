{{-- Banner de consentimento de cookies — LGPD / GDPR / CCPA / POPIA --}}
<div x-data="cookieConsent()" x-show="show" x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    class="fixed inset-x-0 bottom-0 z-[60] p-4 sm:p-6"
    role="dialog" aria-modal="true" aria-label="Aviso de cookies e privacidade">
    <div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 rounded-2xl shadow-elevated border border-gray-200 dark:border-gray-700 p-5 sm:p-6">
        <div class="flex flex-col md:flex-row md:items-center gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-cookie-bite text-primary-600 dark:text-primary-400"></i>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Nós valorizamos sua privacidade</h2>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                    Utilizamos cookies para melhorar sua experiência, analisar o tráfego e personalizar conteúdo.
                    Ao aceitar, você concorda com o uso conforme nossa
                    <a href="{{ route('privacy') }}" class="text-primary-600 dark:text-primary-400 hover:underline">Política de Privacidade</a>.
                    Você pode recusar os cookies não essenciais. Em conformidade com LGPD, GDPR, CCPA e POPIA.
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 shrink-0">
                <button type="button" @click="decline()"
                    class="px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Recusar não essenciais
                </button>
                <button type="button" @click="accept()"
                    class="px-4 py-2.5 text-sm font-medium rounded-lg bg-primary-600 hover:bg-primary-700 text-white transition">
                    Aceitar todos
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function cookieConsent() {
        return {
            show: false,
            init() {
                // Exibe apenas se ainda não houver decisão registrada.
                this.show = !localStorage.getItem('cookie_consent');
            },
            accept() {
                localStorage.setItem('cookie_consent', 'accepted');
                localStorage.setItem('cookie_consent_at', new Date().toISOString());
                this.show = false;
                // Signal the analytics loader that it may initialize now.
                window.dispatchEvent(new CustomEvent('cookie-consent-accepted'));
            },
            decline() {
                localStorage.setItem('cookie_consent', 'declined');
                localStorage.setItem('cookie_consent_at', new Date().toISOString());
                this.show = false;
            },
        };
    }
</script>
