@props([
    'name' => null,
    'error' => null,
    'rows' => 4,
])

@php
    $hasError = $error ? $errors->has($error) : false;
@endphp

<textarea
    @if($name) name="{{ $name }}" id="{{ $attributes->get('id', $name) }}" @endif
    rows="{{ $rows }}"
    autocomplete="off"
    {{ $attributes->merge(['class' => 'input resize-y' . ($hasError ? ' has-error' : '')]) }}
>{{ $slot }}</textarea>
