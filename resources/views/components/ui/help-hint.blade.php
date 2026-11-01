@props([
    // Help text shown in the popover and exposed to assistive tech.
    'text' => '',
    // Font Awesome icon for the trigger (defaults to a question mark).
    'icon' => 'fa-circle-question',
])

{{--
    Accessible contextual help hint. Unlike a native `title`, this works with
    keyboard (focus), touch (tap) and pointer (hover), and announces the text
    to screen readers via aria-describedby. Used inline next to plan features.
--}}
<span
    x-data="{ open: false }"
    class="relative inline-flex align-middle"
    @mouseenter="open = true"
    @mouseleave="open = false"
>
    <button
        type="button"
        class="text-surface-400 dark:text-surface-500 hover:text-primary-600 dark:hover:text-primary-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded-full transition-colors"
        :aria-expanded="open.toString()"
        aria-label="Ajuda"
        {{ $attributes->merge(['aria-describedby' => 'hint-' . ($hintId = 'h' . substr(md5($text), 0, 8))]) }}
        @click="open = !open"
        @focus="open = true"
        @blur="open = false"
        @keydown.escape.stop="open = false"
    >
        <i class="fas {{ $icon }} text-xs" aria-hidden="true"></i>
    </button>
    <span
        id="{{ $hintId }}"
        role="tooltip"
        x-show="open"
        x-cloak
        x-transition.opacity
        class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-56 max-w-[70vw] px-3 py-2 rounded-xl bg-surface-900 dark:bg-surface-700 text-white text-xs font-normal leading-relaxed shadow-lg z-20 text-left"
    >
        {{ $text }}
    </span>
</span>
