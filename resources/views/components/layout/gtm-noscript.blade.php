{{-- Google Tag Manager (noscript fallback): only when GTM is configured AND the --}}
{{-- consent cookie already grants analytics (no tracking request before consent). --}}
@if(config('services.google.gtm_id') && app(\App\Interfaces\Services\ConsentServiceInterface::class)->allows(request(), 'analytics'))
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ urlencode((string) config('services.google.gtm_id')) }}" height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>
@endif
