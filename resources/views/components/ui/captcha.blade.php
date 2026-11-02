@props([
    // Nome da ação enviada ao Google (reCAPTCHA v3). Deve ser igual no servidor:
    // login, register, forgot_password, reset_password, resend_verification.
    'action' => 'submit',
])

@inject('captcha', \App\Interfaces\Services\CaptchaServiceInterface::class)

{{--
    Proteção anti-bot: Google reCAPTCHA v3 (invisível, por pontuação) e/ou
    Cloudflare Turnstile. Cada provedor só aparece quando suas chaves estão no
    .env; sem chaves o componente não renderiza nada.
    Usar dentro do <form>, depois de @csrf.
--}}
@if($captcha->enabled())
<div {{ $attributes->class(['space-y-2']) }} data-captcha>
    @if($captcha->turnstileEnabled())
        <div class="flex justify-center">
            <div class="cf-turnstile"
                data-sitekey="{{ config('captcha.turnstile.site_key') }}"
                data-action="{{ $action }}"
                data-theme="auto"
                data-language="pt-br"></div>
        </div>
        @error(\App\Interfaces\Services\CaptchaServiceInterface::TURNSTILE_FIELD)
            <p class="input-error justify-center"><i class="fas fa-circle-exclamation"></i>{{ $message }}</p>
        @enderror
    @endif

    @if($captcha->recaptchaEnabled())
        <input type="hidden" name="{{ \App\Interfaces\Services\CaptchaServiceInterface::RECAPTCHA_FIELD }}" value="" data-recaptcha-action="{{ $action }}">
        @error(\App\Interfaces\Services\CaptchaServiceInterface::RECAPTCHA_FIELD)
            <p class="input-error justify-center"><i class="fas fa-circle-exclamation"></i>{{ $message }}</p>
        @enderror
    @endif

    <p class="text-center text-xs text-surface-500 dark:text-surface-400">
        <i class="fas fa-shield-halved mr-1"></i>
        Protegido contra robôs
        @if($captcha->recaptchaEnabled())
            por reCAPTCHA. Aplicam-se a
            <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer" class="underline hover:text-primary-600">Política de Privacidade</a> e os
            <a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer" class="underline hover:text-primary-600">Termos de Serviço</a> do Google.
        @else
            pelo Cloudflare Turnstile.
        @endif
    </p>
    <p class="hidden text-center text-xs text-red-600 dark:text-red-400" data-captcha-wait role="alert">
        Aguarde a verificação de segurança terminar e envie novamente.
    </p>
</div>

@once
    @if($captcha->turnstileEnabled())
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
    @if($captcha->recaptchaEnabled())
        <script src="https://www.google.com/recaptcha/api.js?render={{ config('captcha.recaptcha.site_key') }}" async defer></script>
    @endif
    <script>
        (function () {
            var recaptchaKey = @json($captcha->recaptchaEnabled() ? config('captcha.recaptcha.site_key') : null);
            // reCAPTCHA tokens expire after 2 minutes: renew them a bit earlier.
            var TOKEN_TTL = 100000;

            function whenGrecaptchaReady(cb) {
                if (window.grecaptcha && window.grecaptcha.ready) {
                    window.grecaptcha.ready(cb);
                } else {
                    setTimeout(function () { whenGrecaptchaReady(cb); }, 150);
                }
            }

            function fillToken(input) {
                return new Promise(function (resolve) {
                    whenGrecaptchaReady(function () {
                        window.grecaptcha.execute(recaptchaKey, { action: input.dataset.recaptchaAction })
                            .then(function (token) {
                                input.value = token;
                                input.dataset.issuedAt = String(Date.now());
                                resolve(token);
                            })
                            .catch(function () { resolve(''); });
                    });
                });
            }

            function refreshAll() {
                document.querySelectorAll('input[data-recaptcha-action]').forEach(fillToken);
            }

            function submitAgain(form) {
                if (typeof form.requestSubmit === 'function') { form.requestSubmit(); } else { form.submit(); }
            }

            document.addEventListener('DOMContentLoaded', function () {
                if (recaptchaKey) {
                    refreshAll();
                    setInterval(refreshAll, TOKEN_TTL);

                    document.querySelectorAll('input[data-recaptcha-action]').forEach(function (input) {
                        if (!input.form) { return; }
                        // Capture phase: runs before the double-submit guard in forms.js.
                        input.form.addEventListener('submit', function (event) {
                            var fresh = input.value && (Date.now() - Number(input.dataset.issuedAt || 0)) < TOKEN_TTL;
                            if (fresh) { return; }
                            event.preventDefault();
                            event.stopImmediatePropagation();
                            fillToken(input).then(function () { submitAgain(input.form); });
                        }, true);
                    });
                }

                // Turnstile: do not post before the widget produced its token.
                document.querySelectorAll('.cf-turnstile').forEach(function (widget) {
                    var form = widget.closest('form');
                    if (!form) { return; }
                    form.addEventListener('submit', function (event) {
                        var field = form.querySelector('input[name="cf-turnstile-response"]');
                        if (field && field.value) { return; }
                        event.preventDefault();
                        event.stopImmediatePropagation();
                        var hint = form.querySelector('[data-captcha-wait]');
                        if (hint) { hint.classList.remove('hidden'); }
                    }, true);
                });
            });

            window.addEventListener('pageshow', function (event) {
                if (event.persisted && recaptchaKey) { refreshAll(); }
            });
        })();
    </script>
@endonce
@endif
