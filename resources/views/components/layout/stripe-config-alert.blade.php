{{-- Admin-only warning: production is running with a Stripe test key. --}}
@inject('stripeCheck', \App\Interfaces\Services\StripeConfigCheckInterface::class)
@if(auth()->user()?->isAdmin() && $stripeCheck->isTestKeyInProduction())
<div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-4">
    <x-ui.alert variant="danger" title="Configuração do Stripe" role="alert">
        Atenção: o ambiente de produção está usando uma chave de teste do Stripe (sk_test_). Pagamentos reais não serão processados. Atualize STRIPE_SECRET_KEY no servidor.
    </x-ui.alert>
</div>
@endif
