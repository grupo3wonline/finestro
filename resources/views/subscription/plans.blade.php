@extends('layouts.app')

@section('title', 'Planos — Oferta por Tempo Limitado')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('subscription.index') }}" class="text-gray-500 hover:text-primary-600">Assinatura</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Planos</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <!-- Urgency Banner -->
    <div class="bg-gradient-to-r from-red-600 to-orange-500 rounded-xl p-4 text-center text-white shadow-lg animate-pulse" x-data="promoCountdown()" x-init="startTimer()">
        <div class="flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4">
            <span class="flex items-center gap-2 font-bold text-sm sm:text-base">
                <i class="fas fa-fire text-yellow-300"></i> OFERTA RELÂMPAGO — 40% OFF no Plano Profissional
            </span>
            <span class="bg-white/20 backdrop-blur px-3 py-1 rounded-full text-xs sm:text-sm font-mono font-bold" x-text="timer"></span>
        </div>
        <p class="text-xs mt-1 text-white/80">Restam poucas vagas com este desconto. Após o prazo, o valor volta para R$ 41,58/mês.</p>
    </div>

    <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Escolha seu Plano</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Invista no controle total das suas finanças. Você merece mais.</p>
    </div>

    <div class="grid md:grid-cols-2 gap-8 mt-8">
        <!-- Free Plan -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border-2 {{ $currentPlan === 'free' ? 'border-primary-500' : 'border-gray-200 dark:border-gray-700' }} p-8 relative opacity-90">
            @if($currentPlan === 'free')
            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                <span class="bg-gray-500 text-white text-xs font-bold px-3 py-1 rounded-full">PLANO ATUAL</span>
            </div>
            @endif

            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user text-2xl text-gray-600 dark:text-gray-300"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Básico</h3>
                <div class="mt-2">
                    <span class="text-4xl font-bold text-gray-900 dark:text-white">Grátis</span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Recursos limitados</p>
            </div>

            <ul class="space-y-3 mb-8">
                @foreach($plans['free']['features'] as $feature)
                <li class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <i class="fas fa-check text-green-500 flex-shrink-0"></i>
                    {{ $feature }}
                </li>
                @endforeach
                <li class="flex items-center gap-2 text-sm text-gray-400">
                    <i class="fas fa-lock text-red-400 flex-shrink-0"></i>
                    Simuladores financeiros
                </li>
                <li class="flex items-center gap-2 text-sm text-gray-400">
                    <i class="fas fa-lock text-red-400 flex-shrink-0"></i>
                    Exportação de relatórios
                </li>
                <li class="flex items-center gap-2 text-sm text-gray-400">
                    <i class="fas fa-lock text-red-400 flex-shrink-0"></i>
                    Inteligência Artificial
                </li>
                <li class="flex items-center gap-2 text-sm text-gray-400">
                    <i class="fas fa-lock text-red-400 flex-shrink-0"></i>
                    Suporte prioritário
                </li>
            </ul>

            @if($currentPlan === 'free')
            <div class="text-center">
                <button disabled class="w-full px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-medium rounded-xl cursor-not-allowed">
                    Plano Atual
                </button>
                <p class="text-xs text-red-500 mt-2 font-medium"><i class="fas fa-exclamation-triangle mr-1"></i> Você está perdendo recursos que poderiam mudar sua vida financeira.</p>
            </div>
            @else
            <p class="text-center text-sm text-gray-500">Cancele sua assinatura para voltar ao plano gratuito.</p>
            @endif
        </div>

        <!-- Professional Plan -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border-2 border-primary-500 dark:border-primary-400 p-8 relative ring-2 ring-primary-500/20 transform md:scale-105">
            @if($currentPlan === 'professional')
            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                <span class="bg-primary-600 text-white text-xs font-bold px-3 py-1 rounded-full">PLANO ATUAL</span>
            </div>
            @else
            <div class="absolute -top-3 left-1/2 -translate-x-1/2 flex gap-2">
                <span class="bg-red-600 text-white text-xs font-bold px-3 py-1 rounded-full animate-bounce">🔥 OFERTA LIMITADA</span>
            </div>
            @endif

            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-crown text-2xl text-primary-600"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Profissional</h3>

                <!-- Pricing with anchor -->
                <div class="mt-3">
                    <div class="flex items-center justify-center gap-2 mb-1">
                        <span class="text-lg text-gray-400 line-through font-medium">R$ 41,58</span>
                        <span class="bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-bold px-2 py-0.5 rounded-full">-40% OFF</span>
                    </div>
                    <span class="text-5xl font-black text-primary-600 dark:text-primary-400">R$ 24,90</span>
                    <span class="text-gray-500 dark:text-gray-400">/mês</span>
                </div>
                <p class="text-xs text-red-500 dark:text-red-400 mt-2 font-semibold"><i class="fas fa-clock mr-1"></i> Preço promocional por tempo limitado</p>
            </div>

            <!-- Social Proof -->
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3 mb-4 text-center">
                <p class="text-xs text-green-700 dark:text-green-400 font-medium">
                    <i class="fas fa-users mr-1"></i> +{{ rand(47, 123) }} pessoas assinaram esta semana
                </p>
            </div>

            <ul class="space-y-3 mb-6">
                @foreach($plans['professional']['features'] as $feature)
                <li class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <i class="fas fa-check-circle text-green-500 flex-shrink-0"></i>
                    <strong>{{ $feature }}</strong>
                </li>
                @endforeach
            </ul>

            @if($currentPlan === 'professional')
            <button disabled class="w-full px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-medium rounded-xl cursor-not-allowed">
                Plano Atual
            </button>
            @else
            <form action="{{ route('subscription.checkout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full px-6 py-4 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-black rounded-xl transition shadow-xl text-lg group">
                    <i class="fas fa-bolt mr-2 group-hover:animate-pulse"></i> ASSINAR AGORA — R$ 24,90/mês
                </button>
            </form>
            <div class="mt-3 text-center space-y-1">
                <p class="text-xs text-gray-500 dark:text-gray-400"><i class="fas fa-shield-alt text-green-500 mr-1"></i> Pagamento 100% seguro via Stripe</p>
                <p class="text-xs text-gray-500 dark:text-gray-400"><i class="fas fa-undo text-blue-500 mr-1"></i> Cancele quando quiser, sem burocracia</p>
                <p class="text-xs text-red-500 dark:text-red-400 font-semibold"><i class="fas fa-hourglass-half mr-1"></i> Últimas vagas com desconto de 40%</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Urgency Reminder -->
    @if($currentPlan !== 'professional')
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 rounded-xl p-6 text-center">
        <h3 class="text-lg font-bold text-yellow-800 dark:text-yellow-300 mb-2">
            <i class="fas fa-exclamation-triangle mr-2"></i> Não perca esta oportunidade
        </h3>
        <p class="text-sm text-yellow-700 dark:text-yellow-400 mb-3">
            O valor de <strong>R$ 24,90/mês</strong> é exclusivo para novos assinantes.
            Em breve o preço volta para <strong class="line-through">R$ 41,58/mês</strong>.
            Garanta agora e economize <strong>R$ 200,16 por ano</strong>.
        </p>
        <form action="{{ route('subscription.checkout') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="inline-flex items-center px-6 py-3 bg-yellow-600 hover:bg-yellow-700 text-white font-bold rounded-xl transition shadow-lg">
                <i class="fas fa-rocket mr-2"></i> Garantir Desconto de 40% Agora
            </button>
        </form>
    </div>
    @endif

    <!-- FAQ -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-question-circle text-primary-500 mr-2"></i> Perguntas Frequentes
        </h3>
        <div class="space-y-4">
            <div>
                <h4 class="font-medium text-gray-900 dark:text-white text-sm">Posso cancelar a qualquer momento?</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Sim! Cancele quando quiser sem taxas. Acesso mantido até o fim do período pago.</p>
            </div>
            <div>
                <h4 class="font-medium text-gray-900 dark:text-white text-sm">O desconto de 40% é permanente?</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400"><strong>Não.</strong> Este desconto é exclusivo para quem assinar durante a promoção. Após o prazo, o valor será R$ 41,58/mês. Quem assinar agora mantém o preço de R$ 24,90 enquanto a assinatura estiver ativa.</p>
            </div>
            <div>
                <h4 class="font-medium text-gray-900 dark:text-white text-sm">O que acontece com meus dados se eu voltar ao plano gratuito?</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Seus dados são mantidos. Porém, você não poderá criar novos recursos além dos limites do plano gratuito.</p>
            </div>
            <div>
                <h4 class="font-medium text-gray-900 dark:text-white text-sm">O pagamento é seguro?</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">100% seguro! Utilizamos o Stripe, a plataforma de pagamento mais confiável do mundo. Seus dados nunca passam pelo nosso servidor.</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function promoCountdown() {
    return {
        timer: '00:00:00:00',
        startTimer() {
            const now = new Date();
            // Promo ends at end of current month
            const end = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59);

            const update = () => {
                const diff = end - new Date();
                if (diff <= 0) { this.timer = 'ENCERRADO'; return; }
                const d = Math.floor(diff / 86400000);
                const h = Math.floor((diff % 86400000) / 3600000);
                const m = Math.floor((diff % 3600000) / 60000);
                const s = Math.floor((diff % 60000) / 1000);
                this.timer = `${d}d ${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
            };
            update();
            setInterval(update, 1000);
        }
    }
}
</script>
@endpush
@endsection
