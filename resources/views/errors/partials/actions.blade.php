{{-- Shared "way back" actions for error pages. No DB access, no exception details. --}}
{{-- Pass ['reload' => true] to offer "Tentar novamente" instead of "Voltar". --}}
<div class="flex flex-col sm:flex-row flex-wrap gap-3 justify-center">
    @auth
        <x-ui.button :href="route('dashboard')" icon="fa-tachometer-alt">Ir para o Dashboard</x-ui.button>
        <x-ui.button :href="route('home')" variant="outline" icon="fa-home">Página inicial</x-ui.button>
    @else
        <x-ui.button :href="route('home')" icon="fa-home">Página inicial</x-ui.button>
    @endauth
    @if(!empty($reload))
        <x-ui.button href="javascript:location.reload()" variant="outline" icon="fa-rotate-right" data-no-loading>Tentar novamente</x-ui.button>
    @else
        <x-ui.button href="javascript:history.back()" variant="outline" icon="fa-arrow-left" data-no-loading>Voltar</x-ui.button>
    @endif
    <x-ui.button :href="route('faq')" variant="ghost" icon="fa-circle-question">Central de Ajuda</x-ui.button>
</div>
