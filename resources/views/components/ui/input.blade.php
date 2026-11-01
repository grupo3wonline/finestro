@props([
    'type' => 'text',
    'name' => null,
    'icon' => null,       // ícone Font Awesome à esquerda (ex.: 'fa-envelope')
    'error' => null,      // nome do campo p/ marcar estado de erro
])

@php
    $hasError = $error ? $errors->has($error) : false;
    $inputClass = 'input' . ($hasError ? ' has-error' : '');
    // autocomplete="off" por padrão; pode ser sobrescrito passando o atributo
    // (ex.: autocomplete="new-password" para gerenciadores de senha legítimos).
    $autocomplete = $attributes->get('autocomplete', 'off');
@endphp

@if($icon)
<div class="input-group">
    <span class="input-group-icon"><i class="fas {{ $icon }}"></i></span>
    <input
        type="{{ $type }}"
        @if($name) name="{{ $name }}" id="{{ $attributes->get('id', $name) }}" @endif
        autocomplete="{{ $autocomplete }}"
        {{ $attributes->except('autocomplete')->merge(['class' => $inputClass]) }}
    >
</div>
@else
    <input
        type="{{ $type }}"
        @if($name) name="{{ $name }}" id="{{ $attributes->get('id', $name) }}" @endif
        autocomplete="{{ $autocomplete }}"
        {{ $attributes->except('autocomplete')->merge(['class' => $inputClass]) }}
    >
@endif
