@props([
    'title' => null,
    'icon' => null,
    'iconColor' => 'primary',   // primary | secondary | accent | red
    'bodyClass' => 'p-6',
])

@php
    $iconTint = [
        'primary' => 'text-primary-500',
        'secondary' => 'text-secondary-500',
        'accent' => 'text-accent-500',
        'red' => 'text-red-500',
    ][$iconColor] ?? 'text-primary-500';
@endphp

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-surface-900 rounded-2xl shadow-card border border-surface-100 dark:border-surface-800 overflow-hidden']) }}>
    @if($title || isset($header))
        <div class="px-6 py-4 border-b border-surface-100 dark:border-surface-800 flex items-center justify-between gap-3">
            @isset($header)
                {{ $header }}
            @else
                <h2 class="text-lg font-semibold text-surface-900 dark:text-white flex items-center gap-2">
                    @if($icon)<i class="fas {{ $icon }} {{ $iconTint }}"></i>@endif
                    {{ $title }}
                </h2>
            @endisset
            @isset($actions)
                <div class="flex items-center gap-2 shrink-0">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div class="{{ $bodyClass }}">
        {{ $slot }}
    </div>
</div>
