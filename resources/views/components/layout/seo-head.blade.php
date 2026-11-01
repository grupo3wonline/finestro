{{-- SEO / GEO / AEO head tags. Pages set @section('title'), @section('meta_description') --}}
{{-- and optionally @section('meta_keywords'); extra JSON-LD goes to @push('jsonld'). --}}
@php
    $seoTitle = View::getSection('title', config('seo.default_title'));
    $seoDescription = View::getSection('meta_description', config('seo.default_description'));
    $seoKeywords = View::getSection('meta_keywords', 'finanças pessoais, controle financeiro, gestão de dinheiro, carteiras digitais, metas financeiras, simulador financeiro, orçamento pessoal');
    $seoRouteName = request()->route()?->getName();
    // Only the public pages listed in config/seo.php are indexable; auth, app and error pages are not.
    $seoIndexable = in_array($seoRouteName, config('seo.indexable_routes', []), true) && ! isset($exception);
    $seoRobots = $seoIndexable ? 'index, follow, max-image-preview:large, max-snippet:-1' : 'noindex, nofollow';
    $seoCanonical = url()->current();
    $seoImage = asset(config('seo.og_image', 'og-image.png'));
    $seoImageAlt = 'Finestro - Gestão financeira pessoal inteligente, por Grupo 3W Online';
    $structuredData = app(\App\Interfaces\Services\StructuredDataServiceInterface::class);
@endphp
<meta name="description" content="{{ $seoDescription }}">
<meta name="keywords" content="{{ $seoKeywords }}">
<meta name="author" content="HOTWYL | WILLFROMBRASIL">
<meta name="copyright" content="&copy; {{ date('Y') }} Finestro - Grupo 3W Online">
<meta name="robots" content="{{ $seoRobots }}">
<link rel="canonical" href="{{ $seoCanonical }}">

{{-- GEO Targeting (local/regional SEO) --}}
<meta name="geo.region" content="BR">
<meta name="geo.placename" content="Brasil">
<meta name="language" content="Portuguese">
<meta http-equiv="content-language" content="pt-BR">

{{-- hreflang (single pt-BR version) --}}
<link rel="alternate" hreflang="pt-BR" href="{{ $seoCanonical }}">
<link rel="alternate" hreflang="x-default" href="{{ $seoCanonical }}">

{{-- Open Graph --}}
<meta property="og:title" content="{{ config('app.name') }} - {{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:locale" content="pt_BR">
<meta property="og:image" content="{{ $seoImage }}">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $seoImageAlt }}">

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ config('app.name') }} - {{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">
<meta name="twitter:image:alt" content="{{ $seoImageAlt }}">

{{-- Icons / PWA --}}
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="alternate icon" href="{{ asset('favicon.ico') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#039855">
<meta name="msapplication-TileColor" content="#039855">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Finestro">
<meta name="mobile-web-app-capable" content="yes">
<meta name="application-name" content="Finestro">
<meta name="format-detection" content="telephone=no">

{{-- Structured data (JSON-LD), built from PHP arrays --}}
@if($seoRouteName === 'home' && ! isset($exception))
    <x-seo.json-ld :data="$structuredData->organization()" />
    <x-seo.json-ld :data="$structuredData->website()" />
    <x-seo.json-ld :data="$structuredData->softwareApplication()" />
    {{-- Same source as the visible #faq and #como-usar sections --}}
    <x-seo.json-ld :data="$structuredData->faqPage(\App\Support\Landing\LandingContent::faq())" />
    <x-seo.json-ld :data="$structuredData->howTo(\App\Support\Landing\LandingContent::steps())" />
@elseif($seoRouteName === 'contact' && ! isset($exception))
    <x-seo.json-ld :data="$structuredData->organization()" />
@endif
@stack('jsonld')
