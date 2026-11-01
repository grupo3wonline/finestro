@props([
    // Array de itens: ['label' => 'Carteiras', 'url' => route('wallets.index')]
    // O último item (sem 'url' ou marcado como atual) é renderizado como página atual.
    'items' => [],
])

@php
    $items = collect($items)->filter()->values();
    $lastIndex = $items->count() - 1;
    // Root: dashboard for signed-in users, public home for guests.
    $rootUrl = auth()->check() ? route('dashboard') : route('home');
@endphp

@if($items->isNotEmpty())
<nav class="text-sm" aria-label="Trilha de navegação">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
        {{-- Home sempre como raiz --}}
        <li class="flex items-center">
            <a href="{{ $rootUrl }}" class="text-surface-500 dark:text-surface-400 hover:text-primary-600 dark:hover:text-primary-400 transition" title="Início" aria-label="Início">
                <i class="fas fa-home"></i>
            </a>
        </li>

        @foreach($items as $index => $item)
            <li class="flex items-center">
                <i class="fas fa-chevron-right text-surface-400 text-xs mx-1" aria-hidden="true"></i>
            </li>
            <li class="flex items-center">
                @if($index === $lastIndex || empty($item['url']))
                    <span class="text-surface-700 dark:text-surface-300 font-medium" aria-current="page">{{ $item['label'] }}</span>
                @else
                    <a href="{{ $item['url'] }}" class="text-surface-500 dark:text-surface-400 hover:text-primary-600 dark:hover:text-primary-400 transition">{{ $item['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

{{-- Structured Data: BreadcrumbList (SEO) --}}
<x-seo.json-ld :data="app(\App\Interfaces\Services\StructuredDataServiceInterface::class)->breadcrumbList(
    $items->map(fn (array $item): array => ['label' => (string) $item['label'], 'url' => $item['url'] ?? null])
        ->prepend(['label' => 'Início', 'url' => $rootUrl])
        ->all()
)" />
@endif
