@props([
    'label' => null,
    'for' => null,
    'hint' => null,
    'error' => null,      // nome do campo para @error, ou mensagem direta
    'required' => false,
    'tooltip' => null,    // texto de ajuda exibido em tooltip no label
])

@php
    // Resolve a mensagem de erro: se $error casar com um erro de validação, usa-o.
    $errorMessage = null;
    if ($error) {
        $errorMessage = $errors->first($error) ?: (str_contains($error, ' ') ? $error : null);
    }
@endphp

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if($label)
        <label @if($for) for="{{ $for }}" @endif class="input-label mb-0 flex items-center gap-1">
            {{ $label }}
            @if($required)<span class="text-red-500">*</span>@endif
            @if($tooltip)
                <span class="text-surface-400 dark:text-surface-500 cursor-help" title="{{ $tooltip }}">
                    <i class="fas fa-circle-question text-xs"></i>
                </span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if($hint && !$errorMessage)
        <p class="input-hint">{{ $hint }}</p>
    @endif
    @if($errorMessage)
        <p class="input-error"><i class="fas fa-circle-exclamation"></i>{{ $errorMessage }}</p>
    @endif
</div>
