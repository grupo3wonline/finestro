@props([
    // Variantes: info | success | warning | danger
    'variant' => 'info',
    'icon' => null,
    'title' => null,
    'dismissible' => false,
])

@php
    $class = [
        'info'    => 'alert-info',
        'success' => 'alert-success',
        'warning' => 'alert-warning',
        'danger'  => 'alert-danger',
    ][$variant] ?? 'alert-info';

    $defaultIcon = [
        'info' => 'fa-circle-info',
        'success' => 'fa-circle-check',
        'warning' => 'fa-triangle-exclamation',
        'danger' => 'fa-circle-exclamation',
    ][$variant] ?? 'fa-circle-info';
    $icon = $icon ?? $defaultIcon;
@endphp

<div {{ $attributes->merge(['class' => $class]) }}
    @if($dismissible) x-data="{ show: true }" x-show="show" x-transition @endif>
    <i class="fas {{ $icon }} mt-0.5 shrink-0"></i>
    <div class="flex-1 min-w-0">
        @if($title)<p class="font-semibold mb-0.5">{{ $title }}</p>@endif
        <div>{{ $slot }}</div>
    </div>
    @if($dismissible)
        <button type="button" @click="show = false" class="shrink-0 opacity-60 hover:opacity-100 transition" aria-label="Fechar">
            <i class="fas fa-xmark"></i>
        </button>
    @endif
</div>
