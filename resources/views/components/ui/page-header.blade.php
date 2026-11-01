@props([
    'title' => '',
    'subtitle' => null,
    'icon' => null,
])

{{-- Cabeçalho padrão de página: título + subtítulo à esquerda, ações (slot) à direita. --}}
<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4']) }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-bold text-surface-900 dark:text-white flex items-center gap-2.5">
            @if($icon)
                <span class="icon-box icon-box-sm bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400"><i class="fas {{ $icon }}"></i></span>
            @endif
            {{ $title }}
        </h1>
        @if($subtitle)
            <p class="text-surface-500 dark:text-surface-300 text-sm mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2 shrink-0">{{ $actions }}</div>
    @endisset
</div>
