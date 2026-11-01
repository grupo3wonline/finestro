@php
    // Shared contact channels (landing #contato and /contato). Direct links
    // only: no form, no third-party iframe.
    $whatsappUrl = 'https://wa.me/'.config('marketing.contact.whatsapp_number').'?text='.rawurlencode((string) config('marketing.contact.whatsapp_text'));
    $email = (string) config('marketing.contact.email');
@endphp

{{-- Contact channels (direct links: WhatsApp + e-mail, no form) --}}
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 max-w-5xl mx-auto">
    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
        class="group bg-surface-50 dark:bg-surface-800 rounded-2xl p-6 border border-surface-200 dark:border-surface-700 hover:border-green-300 dark:hover:border-green-700 hover:shadow-md transition-all duration-300 flex flex-col items-center text-center"
        title="Abrir conversa no WhatsApp">
        <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg shadow-green-500/20 group-hover:scale-110 transition-transform duration-300 mb-4">
            <i class="fab fa-whatsapp text-white text-2xl" aria-hidden="true"></i>
        </div>
        <h3 class="text-lg font-bold text-surface-900 dark:text-white">WhatsApp</h3>
        <p class="text-surface-600 dark:text-surface-400 text-sm mb-2">Atendimento rápido e direto</p>
        <span class="inline-flex items-center text-green-600 dark:text-green-400 font-semibold"><i class="fas fa-phone mr-2" aria-hidden="true"></i> {{ config('marketing.contact.whatsapp_display') }}</span>
    </a>

    <a href="mailto:{{ $email }}" class="group bg-surface-50 dark:bg-surface-800 rounded-2xl p-6 border border-surface-200 dark:border-surface-700 hover:border-primary-300 dark:hover:border-primary-700 hover:shadow-md transition-all duration-300 flex flex-col items-center text-center"
        title="Enviar um e-mail">
        <div class="w-14 h-14 bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl flex items-center justify-center shadow-lg shadow-primary-500/20 group-hover:scale-110 transition-transform duration-300 mb-4">
            <i class="fas fa-envelope text-white text-xl" aria-hidden="true"></i>
        </div>
        <h3 class="text-lg font-bold text-surface-900 dark:text-white">E-mail</h3>
        <p class="text-surface-600 dark:text-surface-400 text-sm mb-2">Para dúvidas, sugestões e solicitações</p>
        <span class="inline-flex items-center text-primary-600 dark:text-primary-400 font-semibold text-sm"><i class="fas fa-at mr-2" aria-hidden="true"></i> {{ $email }}</span>
    </a>

    <div class="bg-surface-50 dark:bg-surface-800 rounded-2xl p-6 border border-surface-200 dark:border-surface-700 flex flex-col items-center text-center">
        <div class="w-14 h-14 bg-secondary-100 dark:bg-secondary-900/30 rounded-2xl flex items-center justify-center mb-4"><i class="fas fa-clock text-secondary-600 text-xl" aria-hidden="true"></i></div>
        <h3 class="font-semibold text-surface-900 dark:text-white">Horário de Atendimento</h3>
        <p class="text-sm text-surface-600 dark:text-surface-400">Seg-Sex: 9h às 18h</p>
    </div>

    <div class="bg-surface-50 dark:bg-surface-800 rounded-2xl p-6 border border-surface-200 dark:border-surface-700 flex flex-col items-center text-center">
        <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center mb-4"><i class="fas fa-headset text-purple-600 text-xl" aria-hidden="true"></i></div>
        <h3 class="font-semibold text-surface-900 dark:text-white">Tempo de Resposta</h3>
        <p class="text-sm text-surface-600 dark:text-surface-400">Até 24h úteis</p>
    </div>
</div>

{{-- Developer card (light text on dark bg) --}}
<div class="mt-8 max-w-5xl mx-auto bg-gradient-to-br from-surface-900 to-surface-800 rounded-2xl p-8 text-center border border-surface-700">
    <div class="w-14 h-14 bg-gradient-to-br from-primary-500 to-accent-500 rounded-xl flex items-center justify-center mx-auto mb-5 shadow-lg"><i class="fas fa-code text-white text-xl" aria-hidden="true"></i></div>
    <h3 class="text-xl font-bold text-white mb-3">Desenvolvido pelo Grupo 3W Online</h3>
    <p class="text-surface-300 mb-6 max-w-lg mx-auto">Somos especializados no desenvolvimento de sites e sistemas personalizados sob demanda. Transformamos ideias em soluções digitais de alto impacto.</p>
    <a href="{{ rtrim((string) config('marketing.cta.url'), '/') }}/?utm_source=finestro&amp;utm_medium=contact&amp;utm_campaign=contato" target="_blank" rel="noopener" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary-600 to-accent-600 hover:from-primary-700 hover:to-accent-700 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
        <i class="fas fa-external-link-alt mr-2" aria-hidden="true"></i> Visite nosso site
    </a>
</div>
