@props([
    // Variantes: primary | secondary | accent | danger | success | ghost | outline
    'variant' => 'primary',
    // Tamanhos: sm | md | lg
    'size' => 'md',
    // Ícone Font Awesome (ex.: 'fa-plus'); posição via iconRight
    'icon' => null,
    'iconRight' => false,
    // Se informado, renderiza <a>; senão <button>
    'href' => null,
    'type' => 'button',
    'block' => false,
    'loading' => null, // texto do overlay de loading
])

@php
    $variantClass = [
        'primary' => 'btn-primary',
        'secondary' => 'btn-secondary',
        'accent' => 'btn-accent',
        'danger' => 'btn-danger',
        'success' => 'btn-success',
        'ghost' => 'btn-ghost',
        'outline' => 'btn-outline',
    ][$variant] ?? 'btn-primary';

    $sizeClass = ['sm' => 'btn-sm', 'lg' => 'btn-lg', 'md' => ''][$size] ?? '';
    $classes = trim("$variantClass $sizeClass " . ($block ? 'btn-block' : ''));
    $loadingAttr = $loading ? ['data-loading' => $loading] : [];
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(array_merge(['class' => $classes], $loadingAttr)) }}>
        @if($icon && !$iconRight)<i class="fas {{ $icon }}"></i>@endif
        {{ $slot }}
        @if($icon && $iconRight)<i class="fas {{ $icon }}"></i>@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(array_merge(['class' => $classes], $loadingAttr)) }}>
        @if($icon && !$iconRight)<i class="fas {{ $icon }}"></i>@endif
        {{ $slot }}
        @if($icon && $iconRight)<i class="fas {{ $icon }}"></i>@endif
    </button>
@endif
