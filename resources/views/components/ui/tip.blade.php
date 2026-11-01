@props(['icon' => 'fa-lightbulb'])

{{-- Caixa de dica/ajuda contextual usada ao longo da interface. --}}
<div {{ $attributes->merge(['class' => 'tip-box']) }}>
    <p class="tip-box-text flex items-start gap-2">
        <i class="fas {{ $icon }} mt-0.5"></i>
        <span>{{ $slot }}</span>
    </p>
</div>
