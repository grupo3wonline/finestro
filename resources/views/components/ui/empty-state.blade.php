@props([
    'icon' => 'fa-inbox',
    'title' => 'Nada por aqui ainda',
    'text' => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-16 px-4']) }}>
    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-surface-100 dark:bg-surface-800 flex items-center justify-center">
        <i class="fas {{ $icon }} text-2xl text-surface-400 dark:text-surface-500"></i>
    </div>
    <h3 class="text-lg font-semibold text-surface-900 dark:text-white mb-2">{{ $title }}</h3>
    @if($text)
        <p class="text-surface-500 dark:text-surface-400 mb-6 max-w-sm mx-auto text-sm">{{ $text }}</p>
    @endif
    @isset($action)
        <div class="flex items-center justify-center">{{ $action }}</div>
    @endisset
</div>
