{{-- SEO Meta Tags --}}
@php
    $seoTitle = View::getSection('title', 'Gestão Financeira Pessoal');
    $seoDescription = View::getSection('meta_description', 'Finestro - Controle suas finanças pessoais de forma simples, inteligente e segura. Carteiras, transações, metas e simuladores financeiros.');
    $seoKeywords = View::getSection('meta_keywords', 'finanças pessoais, controle financeiro, gestão de dinheiro, carteiras digitais, metas financeiras, simulador financeiro, orçamento pessoal');
@endphp
<meta name="description" content="{{ $seoDescription }}">
<meta name="keywords" content="{{ $seoKeywords }}">
<meta name="author" content="willfrombrasil | hotwyl">
<meta name="copyright" content="&copy; {{ date('Y') }} Finestro - Grupo 3W Online">
<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
<meta name="image" content="{{ asset('og-image.jpg') }}">
<meta name="generator" content="Laravel {{ app()->version() }}">
<meta name="rating" content="general">
<link rel="canonical" href="{{ url()->current() }}">

{{-- GEO Targeting (local/regional SEO) --}}
<meta name="geo.region" content="BR">
<meta name="geo.placename" content="Brasil">
<meta name="ICBM" content="-14.235004, -51.925280">
<meta name="language" content="Portuguese">
<meta http-equiv="content-language" content="pt-BR">

{{-- hreflang (internationalization signals) --}}
<link rel="alternate" hreflang="pt-BR" href="{{ url()->current() }}">
<link rel="alternate" hreflang="x-default" href="{{ url()->current() }}">

{{-- Open Graph Meta Tags --}}
<meta property="og:title" content="{{ config('app.name') }} - {{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:type" content="WebApplication">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:locale" content="pt_BR">
<meta property="og:image" content="{{ asset('og-image.jpg') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Finestro - Gestão Financeira Pessoal Inteligente">

{{-- Twitter Card Meta Tags --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ config('app.name') }} - {{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ asset('og-image.jpg') }}">
<meta name="twitter:image:alt" content="Finestro - Gestão Financeira Pessoal">

{{-- Favicon --}}
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="alternate icon" href="{{ asset('favicon.ico') }}">
<link rel="manifest" href="{{ asset('manifest.json') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.svg') }}">

{{-- Theme Color --}}
<meta name="theme-color" content="#2563eb">
<meta name="msapplication-TileColor" content="#2563eb">

{{-- iOS Web App Meta Tags --}}
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Finestro">

{{-- Additional Meta Tags --}}
<meta name="format-detection" content="telephone=no">
<meta name="mobile-web-app-capable" content="yes">
<meta name="application-name" content="Finestro">

{{-- Structured Data --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebApplication",
    "name": "Finestro",
    "description": "Gestão financeira pessoal inteligente. Controle carteiras, transações e metas financeiras.",
    "url": "{{ config('app.url') }}",
    "applicationCategory": "FinanceApplication",
    "operatingSystem": "Web Browser",
    "browserRequirements": "Requires JavaScript. Requires HTML5.",
    "offers": {
        "@@type": "Offer",
        "price": "0",
        "priceCurrency": "BRL"
    },
    "author": {
        "@@type": "Organization",
        "name": "Grupo 3W Online",
        "url": "https://www.3wonline.com.br"
    },
    "aggregateRating": {
        "@@type": "AggregateRating",
        "ratingValue": "4.9",
        "ratingCount": "2500"
    },
    "features": [
        "Múltiplas carteiras financeiras",
        "Controle de receitas e despesas",
        "Metas financeiras com progresso",
        "Simuladores de investimento",
        "Relatórios e exportação",
        "Tema claro e escuro"
    ],
    "softwareVersion": "1.0.0",
    "dateCreated": "2024-01-01",
    "dateModified": "{{ now()->format('Y-m-d') }}",
    "keywords": "finanças pessoais, controle financeiro, gestão de dinheiro, carteiras digitais, metas financeiras"
}
</script>

{{-- Structured Data: Organization (AEO / knowledge panel signals) --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Organization",
    "name": "Finestro",
    "url": "{{ config('app.url') }}",
    "logo": "{{ asset('favicon.svg') }}",
    "description": "Plataforma de gestão financeira pessoal para controlar carteiras, transações, metas e simuladores financeiros.",
    "founder": {
        "@@type": "Organization",
        "name": "Grupo 3W Online",
        "url": "https://www.3wonline.com.br"
    },
    "sameAs": [
        "https://www.3wonline.com.br"
    ],
    "areaServed": {
        "@@type": "Country",
        "name": "Brasil"
    }
}
</script>

{{-- Structured Data: FAQ (Answer Engine Optimization) --}}
{{-- Only emitted on the home page, where the matching content is visible --}}
{{-- (Google requires FAQPage markup to reflect on-page content). --}}
@if(request()->routeIs('home'))
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "O que é o Finestro?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "O Finestro é uma plataforma de gestão financeira pessoal que permite controlar carteiras, registrar receitas e despesas, definir metas e usar simuladores e calculadoras financeiras em um só lugar."
            }
        },
        {
            "@@type": "Question",
            "name": "O Finestro é gratuito?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Sim. O Finestro possui um plano gratuito com recursos básicos, além do plano Profissional com carteiras, transações e metas ilimitadas, simuladores, inteligência artificial e relatórios exportáveis."
            }
        },
        {
            "@@type": "Question",
            "name": "Meus dados financeiros estão seguros no Finestro?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Sim. Cada usuário acessa apenas os próprios dados, com criptografia, controle de acesso e conformidade com as leis de privacidade LGPD, GDPR, CCPA e POPIA."
            }
        }
    ]
}
</script>
@endif
