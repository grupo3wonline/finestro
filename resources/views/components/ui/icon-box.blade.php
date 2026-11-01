@props([
    'icon' => 'fa-circle',
    'color' => 'primary',   // primary | secondary | accent | red | surface
    'size' => 'md',         // sm | md | lg
])

@php
    $tint = [
        'primary'   => 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400',
        'secondary' => 'bg-secondary-50 dark:bg-secondary-900/30 text-secondary-600 dark:text-secondary-400',
        'accent'    => 'bg-accent-50 dark:bg-accent-900/30 text-accent-600 dark:text-accent-400',
        'red'       => 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400',
        'surface'   => 'bg-surface-100 dark:bg-surface-800 text-surface-500 dark:text-surface-300',
    ][$color] ?? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400';

    $sizeClass = ['sm' => 'icon-box-sm', 'md' => 'icon-box-md', 'lg' => 'icon-box-lg'][$size] ?? 'icon-box-md';
@endphp

<span {{ $attributes->merge(['class' => "icon-box $sizeClass $tint"]) }}>
    <i class="fas {{ $icon }}"></i>
</span>
