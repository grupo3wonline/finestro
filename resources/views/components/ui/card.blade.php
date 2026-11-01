@props([
    'hover' => false,       // aplica realce/elevação no hover
    'padding' => 'p-6',     // padding interno (passe '' para controlar manualmente)
])

<div {{ $attributes->merge([
    'class' => 'bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 '
        . ($hover ? 'hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200 ' : '')
        . $padding
]) }}>
    {{ $slot }}
</div>
