@props([
    'href' => null,           // se informado, envolve num link
    'size' => 'md',           // sm | md | lg
    'showText' => true,
    'textClass' => 'text-surface-900 dark:text-white',
])

@php
    $mark = ['sm' => 'w-8 h-8 text-xs', 'md' => 'w-9 h-9 text-sm', 'lg' => 'w-12 h-12 text-xl'][$size] ?? 'w-9 h-9 text-sm';
    $text = ['sm' => 'text-lg', 'md' => 'text-xl', 'lg' => 'text-2xl'][$size] ?? 'text-xl';
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 brand-logo']) }}>
    <span class="{{ $mark }} bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center shadow-sm shrink-0">
        <i class="fas fa-chart-line text-white"></i>
    </span>
    @if($showText)
        <span class="font-display {{ $text }} font-semibold {{ $textClass }}">Finestro</span>
    @endif
</{{ $tag }}>
