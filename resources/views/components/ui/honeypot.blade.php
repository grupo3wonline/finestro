@props([
    // Nome do campo isca. Deve parecer um campo real para atrair bots,
    // mas nunca é exibido nem preenchido por humanos.
    'name' => 'website',
])

@php
    // Timestamp assinado (criptografado) para o "time-trap": o back-end
    // rejeita envios instantâneos (típicos de bots) e envios muito antigos.
    $honeypotTime = Crypt::encryptString((string) now()->timestamp);
@endphp

{{--
    Anti-spam honeypot.
    - Campo de texto isca escondido de humanos (via CSS + aria-hidden + tabindex),
      mas visível para bots que preenchem todos os campos do formulário.
    - Campo de tempo assinado para detectar submissões rápidas demais.
    O par de campos é validado no servidor pela regra HoneypotRule / middleware.
--}}
<div aria-hidden="true" class="hidden" style="position:absolute!important;left:-9999px!important;top:-9999px!important;height:0;width:0;overflow:hidden;">
    <label for="{{ $name }}_hp">Não preencha este campo</label>
    <input
        type="text"
        name="{{ $name }}"
        id="{{ $name }}_hp"
        value=""
        tabindex="-1"
        autocomplete="off"
    >
    <input type="hidden" name="{{ $name }}_time" value="{{ $honeypotTime }}">
</div>
