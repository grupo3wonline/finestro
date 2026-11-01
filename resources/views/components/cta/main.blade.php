@inject('pricing', \App\Interfaces\Services\PlanPricingInterface::class)
<!-- CTA Finestro Pro -->
<section class="py-16 bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 relative overflow-hidden">
    <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[size:40px_40px]"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <!-- Badge: real promo (when active) or free-start message -->
        <div class="inline-flex items-center bg-white/10 backdrop-blur border border-white/20 rounded-full px-4 py-1.5 mb-6">
            @if($pricing->isPromoActive())
                <span class="w-2 h-2 bg-accent-400 rounded-full mr-2"></span>
                <span class="text-accent-100 text-sm font-semibold">{{ $pricing->promoLabel() }} até {{ $pricing->promoEndsAt()->format('d/m/Y') }}</span>
            @else
                <span class="w-2 h-2 bg-accent-400 rounded-full mr-2"></span>
                <span class="text-primary-50 text-sm font-semibold">Comece grátis, evolua quando quiser</span>
            @endif
        </div>

        <h3 class="text-2xl md:text-4xl font-bold text-white mb-4">
            Assuma o controle total das suas finanças
        </h3>
        <p class="text-primary-100 mb-6 max-w-2xl mx-auto text-lg">
            Comece grátis hoje e, quando quiser ir além, o Finestro Profissional libera tudo sem limite: IA, simuladores, relatórios exportáveis e muito mais.
        </p>

        <!-- Pricing -->
        <div class="inline-flex items-center bg-white/10 backdrop-blur rounded-full px-6 py-2 mb-6 border border-white/20">
            <span class="text-white/80 text-sm mr-2">Profissional por</span>
            @if($pricing->isPromoActive())
                <span class="text-white/60 line-through text-sm mr-2">{{ $pricing->formattedRegular() }}</span>
            @endif
            <span class="num text-white font-black text-2xl">{{ $pricing->formattedCurrent() }}</span>
            <span class="text-white/80 text-sm">/mês</span>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-4">
            <a href="{{ route('register', \App\Support\Utm::fromRequest(request())) }}" class="inline-flex items-center justify-center px-8 py-4 bg-white text-primary-700 font-bold rounded-xl hover:bg-surface-50 transition shadow-xl text-lg">
                <i class="fas fa-rocket mr-2"></i> Começar Agora — É Grátis
            </a>
        </div>
        <p class="text-xs text-primary-200"><i class="fas fa-shield-alt mr-1"></i> Comece grátis, faça upgrade quando quiser. Cancele a qualquer momento.</p>
        @if($pricing->isPromoActive())
            <p class="text-xs text-accent-200 mt-1 font-semibold"><i class="fas fa-clock mr-1"></i> Valor promocional para assinaturas feitas até {{ $pricing->promoEndsAt()->format('d/m/Y H:i') }}. Depois, {{ $pricing->formattedRegular() }}/mês.</p>
        @endif
    </div>
</section>
