@props([
    'name' => null,
    'error' => null,
])

@php
    $hasError = $error ? $errors->has($error) : false;
@endphp

<select
    @if($name) name="{{ $name }}" id="{{ $attributes->get('id', $name) }}" @endif
    autocomplete="off"
    {{ $attributes->merge(['class' => 'select' . ($hasError ? ' has-error' : '')]) }}
>
    {{ $slot }}
</select>
