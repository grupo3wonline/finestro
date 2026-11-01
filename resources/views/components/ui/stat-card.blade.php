@props([
    'label' => '',
    'value' => '',
    'icon' => null,
    // Cor de destaque: primary | secondary | accent | red | surface
    'color' => 'primary',
    'hint' => null,
    // Tendência opcional: ['dir' => 'up'|'down', 'text' => '+12%']
    'trend' => null,
    'valueClass' => '',
])

@php
    $tint = [
        'primary'   => 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400',
        'secondary' => 'bg-secondary-50 dark:bg-secondary-900/30 text-secondary-600 dark:text-secondary-400',
        'accent'    => 'bg-accent-50 dark:bg-accent-900/30 text-accent-600 dark:text-accent-400',
        'red'       => 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400',
        'surface'   => 'bg-surface-100 dark:bg-surface-800 text-surface-600 dark:text-surface-300',
    ][$color] ?? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400';
@endphp

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-surface-900 rounded-2xl p-5 shadow-card border border-surface-100 dark:border-surface-800 hover:shadow-card-hover transition-all duration-200']) }}>
    <div class="flex items-center justify-between mb-2">
        <span class="text-sm text-surface-500 dark:text-surface-300">{{ $label }}</span>
        @if($icon)
            <span class="icon-box icon-box-sm {{ $tint }}"><i class="fas {{ $icon }}"></i></span>
        @endif
    </div>
    <p class="num text-2xl font-semibold text-surface-900 dark:text-white {{ $valueClass }}">{{ $value }}</p>
    <div class="flex items-center justify-between mt-1">
        @if($hint)<p class="text-xs text-surface-400 dark:text-surface-500">{{ $hint }}</p>@endif
        @if($trend)
            <span class="num inline-flex items-center gap-1 text-xs font-semibold {{ ($trend['dir'] ?? 'up') === 'up' ? 'text-secondary-600 dark:text-secondary-400' : 'text-red-600 dark:text-red-400' }}">
                <i class="fas {{ ($trend['dir'] ?? 'up') === 'up' ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                {{ $trend['text'] ?? '' }}
            </span>
        @endif
    </div>
    {{ $slot }}
</div>
