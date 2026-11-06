@extends('layouts.landing')

@section('title', 'Central de Ajuda')
@section('meta_description', 'Central de Ajuda do Finestro: tire suas dúvidas sobre carteiras, transações, cartão de crédito, metas, orçamentos, relatórios, segurança e LGPD.')

{{-- FAQPage JSON-LD mirrors the questions visible on this page (incl. search results). --}}
@php
    $faqJsonLdItems = collect($categories)
        ->flatMap(fn ($category) => $category->items)
        ->map(fn ($item): array => ['question' => (string) $item->question, 'answer' => (string) $item->answer])
        ->values()
        ->all();
@endphp
@if($faqJsonLdItems !== [])
    @push('jsonld')
        <x-seo.json-ld :data="app(\App\Interfaces\Services\StructuredDataServiceInterface::class)->faqPage($faqJsonLdItems)" />
    @endpush
@endif

@section('content')
<section class="py-16 bg-surface-50 dark:bg-surface-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <div class="mb-8">
            <x-ui.breadcrumb :items="[['label' => 'Central de Ajuda']]" />
        </div>

        {{-- Cabeçalho --}}
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-primary-100 dark:bg-primary-900/40 text-primary-600 dark:text-primary-300 mb-4">
                <i class="fas fa-circle-question text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-surface-900 dark:text-white">Central de Ajuda</h1>
            <p class="mt-2 text-surface-600 dark:text-surface-400">Encontre respostas rápidas para as dúvidas mais comuns sobre o Finestro.</p>
        </div>

        {{-- Busca --}}
        <form autocomplete="off" method="GET" action="{{ route('faq') }}" class="mb-8" role="search">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-surface-400">
                    <i class="fas fa-magnifying-glass"></i>
                </span>
                <input type="search" name="q" value="{{ $term }}" autocomplete="off" data-debounce-submit="700"
                    placeholder="Buscar por uma dúvida (ex.: parcelar, fatura, exportar...)"
                    class="w-full pl-11 pr-28 py-3 rounded-xl border border-surface-200 dark:border-surface-600 bg-white dark:bg-surface-950 text-surface-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                <button type="submit"
                    class="absolute inset-y-0 right-0 my-1.5 mr-1.5 px-4 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium transition">
                    Buscar
                </button>
            </div>
            @if($term !== '')
                <p class="mt-3 text-sm text-surface-500 dark:text-surface-400">
                    {{ $totalResults }} {{ $totalResults === 1 ? 'resultado encontrado' : 'resultados encontrados' }} para
                    <span class="font-medium text-surface-700 dark:text-surface-300">"{{ $term }}"</span>.
                    <a href="{{ route('faq') }}" class="text-primary-600 hover:underline ml-1">Limpar busca</a>
                </p>
            @endif
        </form>

        {{-- Conteúdo --}}
        @forelse($categories as $category)
            <div class="mb-8">
                <h2 class="flex items-center gap-2 text-lg font-semibold text-surface-900 dark:text-white mb-3">
                    <i class="fas {{ $category->icon }} text-primary-600 dark:text-primary-400"></i>
                    {{ $category->name }}
                </h2>

                <div class="space-y-3">
                    @foreach($category->items as $item)
                        <div x-data="{ open: {{ $term !== '' ? 'true' : 'false' }} }"
                            class="bg-white dark:bg-surface-950 rounded-xl border border-surface-100 dark:border-surface-800 shadow-sm overflow-hidden">
                            <button type="button" @click="open = !open"
                                class="w-full flex items-center justify-between gap-4 text-left px-5 py-4"
                                :aria-expanded="open.toString()">
                                <span class="font-medium text-surface-900 dark:text-white">{{ $item->question }}</span>
                                <i class="fas fa-chevron-down text-surface-400 transition-transform shrink-0"
                                    :class="open && 'rotate-180'"></i>
                            </button>
                            <div x-show="open" x-cloak
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0">
                                <div class="px-5 pb-5 text-surface-600 dark:text-surface-300 leading-relaxed">
                                    {{ $item->answer }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center py-16 bg-white dark:bg-surface-950 rounded-xl border border-surface-100 dark:border-surface-800">
                <i class="fas fa-magnifying-glass text-4xl text-surface-300 dark:text-surface-600 mb-4"></i>
                <p class="text-surface-600 dark:text-surface-400">
                    Não encontramos nada para <span class="font-medium">"{{ $term }}"</span>.
                </p>
                <a href="{{ route('faq') }}" class="inline-block mt-4 text-primary-600 hover:underline">Ver todas as perguntas</a>
            </div>
        @endforelse

        {{-- CTA de contato --}}
        <div class="mt-12 text-center bg-primary-600 rounded-2xl px-6 py-10 text-white">
            <h2 class="text-xl font-bold">Não encontrou o que procurava?</h2>
            <p class="mt-2 text-primary-100">Nossa equipe está pronta para ajudar você.</p>
            <a href="{{ route('home') }}#contato"
                class="inline-flex items-center gap-2 mt-5 px-6 py-3 rounded-lg bg-white text-primary-700 font-semibold hover:bg-primary-50 transition">
                <i class="fas fa-envelope"></i> Falar com o suporte
            </a>
        </div>
    </div>
</section>
@endsection
