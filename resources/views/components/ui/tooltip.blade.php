@props([
    'text' => '',
    'position' => 'top', // 'top' | 'right'
])

{{-- Reusable tooltip wrapper. Wrap any trigger element in this component. --}}
<div class="relative inline-block group">
    {{ $slot }}
    @if($text)
        <div
            role="tooltip"
            class="pointer-events-none absolute z-50 invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-opacity duration-150
                bg-surface-900 dark:bg-surface-700 text-white text-xs rounded-lg px-3 py-1.5 whitespace-nowrap shadow-lg
                @if($position === 'right')
                    left-full top-1/2 -translate-y-1/2 ml-2
                @else
                    bottom-full left-1/2 -translate-x-1/2 mb-2
                @endif"
        >
            {{ $text }}
            @if($position === 'right')
                <div class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-surface-900 dark:border-r-surface-700"></div>
            @else
                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-surface-900 dark:border-t-surface-700"></div>
            @endif
        </div>
    @endif
</div>
