@props([
    // Array de itens: ['label' => 'Carteiras', 'url' => route('wallets.index')]
    // O último item (sem 'url' ou marcado como atual) é renderizado como página atual.
    'items' => [],
])

@php
    $items = collect($items)->filter()->values();
    $lastIndex = $items->count() - 1;
@endphp

@if($items->isNotEmpty())
<nav class="text-sm" aria-label="Trilha de navegação">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
        {{-- Home sempre como raiz --}}
        <li class="flex items-center">
            <a href="{{ route('dashboard') }}" class="text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition" title="Início" aria-label="Início">
                <i class="fas fa-home"></i>
            </a>
        </li>

        @foreach($items as $index => $item)
            <li class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 text-xs mx-1" aria-hidden="true"></i>
            </li>
            <li class="flex items-center">
                @if($index === $lastIndex || empty($item['url']))
                    <span class="text-gray-700 dark:text-gray-300 font-medium" aria-current="page">{{ $item['label'] }}</span>
                @else
                    <a href="{{ $item['url'] }}" class="text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition">{{ $item['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

{{-- Structured Data: BreadcrumbList (SEO) --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@@type": "ListItem",
            "position": 1,
            "name": "Início",
            "item": "{{ route('dashboard') }}"
        }
        @foreach($items as $index => $item)
        ,{
            "@@type": "ListItem",
            "position": {{ $index + 2 }},
            "name": "{{ e($item['label']) }}"
            @if(!empty($item['url'])),"item": "{{ $item['url'] }}"@endif
        }
        @endforeach
    ]
}
</script>
@endif
