@props([
    // Variantes: success | danger | warning | info | neutral
    'variant' => 'neutral',
    'icon' => null,
])

@php
    $class = [
        'success' => 'badge-success',
        'danger'  => 'badge-danger',
        'warning' => 'badge-warning',
        'info'    => 'badge-info',
        'neutral' => 'badge bg-surface-100 dark:bg-surface-800 text-surface-600 dark:text-surface-300',
    ][$variant] ?? 'badge bg-surface-100 dark:bg-surface-800 text-surface-600 dark:text-surface-300';
@endphp

<span {{ $attributes->merge(['class' => $class . ' gap-1']) }}>
    @if($icon)<i class="fas {{ $icon }} text-[10px]"></i>@endif
    {{ $slot }}
</span>
