@props(['variant' => 'page'])

@php
    // Grupo 3W Online call to action. Rendered once per page by the layouts.
    // Scarcity/urgency copy is limited to verifiable facts (config-driven
    // monthly capacity, current month, free diagnosis, response time).
    $variant = in_array($variant, ['landing', 'page', 'auth', 'app', 'error'], true) ? $variant : 'page';
    $compact = in_array($variant, ['auth', 'app'], true);
    $ctaUrl = rtrim((string) config('marketing.cta.url'), '/').'/?utm_source=finestro&utm_medium=cta&utm_campaign='.$variant;
    // Capacity line only when the business configured a real positive number.
    $slots = config('marketing.cta.monthly_slots');
    $slots = is_int($slots) && $slots > 0 ? $slots : null;
    $month = now()->translatedFormat('F');

    [$headline, $body, $button] = match ($variant) {
        'landing' => [
            'Seu negócio merece um site ou sistema feito sob medida',
            'O Grupo 3W Online, que desenvolveu o Finestro, cria sites e sistemas personalizados sob demanda: do planejamento ao lançamento, com design moderno, segurança e foco em resultado.',
            'Quero um diagnóstico gratuito',
        ],
        'error' => [
            'Enquanto isso: precisa de um sistema que não deixa você na mão?',
            'O Grupo 3W Online desenvolve sites e sistemas personalizados sob demanda, com monitoramento, segurança e suporte de quem entende do assunto.',
            'Falar com o Grupo 3W Online',
        ],
        'auth' => [
            'Precisa de um site ou sistema sob medida?',
            'O Grupo 3W Online desenvolve sites e sistemas personalizados sob demanda.',
            'Conhecer',
        ],
        'app' => [
            'Quer um sistema como este para a sua empresa?',
            'O Grupo 3W Online desenvolve sites e sistemas personalizados sob demanda.',
            'Pedir diagnóstico gratuito',
        ],
        default => [
            'Tire sua ideia do papel com o Grupo 3W Online',
            'Desenvolvemos sites e sistemas personalizados sob demanda para empresas e profissionais que querem vender mais e trabalhar melhor. Você fala com quem desenvolve, sem intermediários.',
            'Solicitar diagnóstico gratuito',
        ],
    };
@endphp

@if($compact)
<section data-cta="3wonline" aria-label="Grupo 3W Online" class="border-t border-surface-200 dark:border-surface-800 bg-surface-50 dark:bg-surface-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left">
        <p class="text-sm text-surface-600 dark:text-surface-300">
            <i class="fas fa-code text-primary-600 dark:text-primary-400 mr-1.5" aria-hidden="true"></i>
            <strong class="text-surface-900 dark:text-white">{{ $headline }}</strong>
            {{ $body }}
            <span class="text-accent-700 dark:text-accent-400 font-medium">Agenda aberta em {{ $month }}.</span>
        </p>
        <a href="{{ $ctaUrl }}" target="_blank" rel="noopener"
            class="shrink-0 inline-flex items-center justify-center min-h-[40px] px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-surface-900"
            title="Abre o site do Grupo 3W Online em nova aba">
            <i class="fas fa-arrow-up-right-from-square mr-2" aria-hidden="true"></i> {{ $button }}
        </a>
    </div>
</section>
@else
<section data-cta="3wonline" aria-label="Grupo 3W Online" class="py-16 bg-surface-50 dark:bg-surface-900 border-t border-surface-200 dark:border-surface-800">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-surface-900 via-primary-950 to-surface-900 p-8 md:p-12 text-center shadow-elevated">
            <div class="absolute inset-0 bg-grid opacity-40" aria-hidden="true"></div>
            <div class="relative">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-accent-400/15 border border-accent-400/30 text-accent-200 text-sm font-semibold mb-6">
                    <span class="w-2 h-2 rounded-full bg-accent-400 animate-pulse" aria-hidden="true"></span>
                    Agenda aberta em {{ $month }}
                </div>
                <h2 class="font-display text-2xl md:text-4xl font-semibold text-white mb-4">{{ $headline }}</h2>
                <p class="text-surface-200 max-w-2xl mx-auto mb-8 text-base md:text-lg leading-relaxed">{{ $body }}</p>

                <ul class="grid grid-cols-1 gap-3 mx-auto mb-8 text-sm {{ $slots !== null ? 'sm:grid-cols-3 max-w-3xl' : 'sm:grid-cols-2 max-w-xl' }}">
                    @if($slots !== null)
                    <li class="flex items-center justify-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-surface-100">
                        <i class="fas fa-hourglass-half text-accent-400" aria-hidden="true"></i>
                        Atendemos no máximo {{ $slots }} novos projetos por mês
                    </li>
                    @endif
                    <li class="flex items-center justify-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-surface-100">
                        <i class="fas fa-magnifying-glass-chart text-accent-400" aria-hidden="true"></i>
                        Diagnóstico gratuito
                    </li>
                    <li class="flex items-center justify-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-surface-100">
                        <i class="fas fa-clock text-accent-400" aria-hidden="true"></i>
                        Resposta em até 24h úteis
                    </li>
                </ul>

                <a href="{{ $ctaUrl }}" target="_blank" rel="noopener"
                    class="inline-flex items-center justify-center px-8 py-4 rounded-2xl bg-accent-400 hover:bg-accent-300 text-surface-950 font-bold shadow-lg shadow-accent-500/25 transition hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-300 focus-visible:ring-offset-2 focus-visible:ring-offset-surface-900"
                    title="Abre o site do Grupo 3W Online em nova aba">
                    <i class="fas fa-rocket mr-2" aria-hidden="true"></i> {{ $button }}
                </a>
                <p class="mt-4 text-xs text-surface-400">Garanta uma das vagas de {{ $month }}. Sem compromisso: o diagnóstico é gratuito.</p>
            </div>
        </div>
    </div>
</section>
@endif
