<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger">
        <x-slot name="heading">Stripe em modo de teste em produção</x-slot>

        <p>
            A aplicação está em produção usando uma chave secreta de teste do Stripe.
            Pagamentos reais não serão processados. Configure a chave de produção no
            arquivo <code>.env</code> do servidor e limpe o cache de configuração.
        </p>
    </x-filament::section>
</x-filament-widgets::widget>
