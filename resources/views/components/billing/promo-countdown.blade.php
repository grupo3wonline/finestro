{{-- Promo badge + countdown to the real promo end. Renders nothing unless the promo is active (see PlanPricing::isPromoActive). --}}
@inject('pricing', \App\Interfaces\Services\PlanPricingInterface::class)
@if($pricing->isPromoActive())
    @php $promoEnd = $pricing->promoEndsAt(); @endphp
    <div {{ $attributes->merge(['class' => 'inline-flex flex-wrap items-center gap-2 rounded-xl border border-accent-200 dark:border-accent-800/60 bg-accent-50 dark:bg-accent-900/20 px-3 py-2 text-sm text-accent-800 dark:text-accent-200']) }}
        x-data="{
            end: new Date('{{ $promoEnd->toIso8601String() }}').getTime(),
            left: '',
            ended: false,
            timer: null,
            tick() {
                const diff = this.end - Date.now();
                if (Number.isNaN(diff) || diff <= 0) { this.ended = true; clearInterval(this.timer); return; }
                const pad = (n) => String(n).padStart(2, '0');
                const d = Math.floor(diff / 86400000);
                this.left = d + 'd ' + pad(Math.floor((diff % 86400000) / 3600000)) + ':' + pad(Math.floor((diff % 3600000) / 60000)) + ':' + pad(Math.floor((diff % 60000) / 1000));
            },
            init() { this.tick(); this.timer = setInterval(() => this.tick(), 1000); },
            destroy() { clearInterval(this.timer); },
        }"
        data-promo-ends-at="{{ $promoEnd->toIso8601String() }}">
        <span class="inline-flex items-center gap-1 font-semibold"><i class="fas fa-tag"></i> {{ $pricing->promoLabel() }}</span>
        <span x-show="!ended">até {{ $promoEnd->format('d/m/Y H:i') }}</span>
        <span x-show="!ended" class="num font-semibold" x-text="left" aria-hidden="true"></span>
        <span x-show="ended" x-cloak class="font-semibold">Oferta encerrada</span>
    </div>
@endif
