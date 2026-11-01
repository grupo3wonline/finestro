{{-- Consent-gated analytics (LGPD/GDPR/CCPA/POPIA). Only configuration is printed here: --}}
{{-- resources/js/consent.js loads GTM / GA4 / Meta Pixel after the visitor opts in. --}}
@php
    $consentConfig = [
        'gtmId' => config('services.google.gtm_id') ?: null,
        'gaId' => config('services.google.analytics_id') ?: null,
        'pixelId' => config('services.facebook.pixel_id') ?: null,
        'version' => (string) config('legal.policy_version'),
        'endpoint' => route('consent.store'),
        'serverGpc' => app(\App\Interfaces\Services\ConsentServiceInterface::class)->gpcRequested(request()),
    ];
@endphp
<script type="application/json" id="finestro-consent-config">@json($consentConfig)</script>
