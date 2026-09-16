{{-- Google Tag Manager (noscript fallback) — only when GTM is configured --}}
@if(config('services.google.gtm_id'))
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ config('services.google.gtm_id') }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif
