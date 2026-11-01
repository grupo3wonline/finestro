<!DOCTYPE html>
<html lang="pt-BR" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Prevent theme flash (FOUC): apply dark class before first paint --}}
    <script>
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
    @include('components.layout.seo-head')

    <title>{{ config('app.name') }} - @yield('title', 'Gestão Financeira Pessoal')</title>

    <!-- Preconnect -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">

    <!-- Inter (body) + Fraunces (hero display) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
    <!-- Font Awesome (non-blocking) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></noscript>
    <!-- SweetAlert2 (deferred; every Swal call runs on DOMContentLoaded or in an event handler) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
    @include('components.layout.analytics')
    @stack('styles')
</head>
<body class="min-h-screen flex flex-col bg-white dark:bg-surface-950 text-surface-900 dark:text-surface-100 transition-colors duration-300 font-sans">
    @include('components.layout.gtm-noscript')

    <!-- Loading Overlay -->
    @include('components.ui.loading-overlay')

    <!-- Header / Navigation -->
    @include('components.layout.public-header')

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Grupo 3W Online CTA -->
    <x-cta.agency :variant="View::getSection('agency_cta_variant', 'page')" />

    <!-- Footer -->
    @include('components.layout.footer')

    <!-- Cookie Consent (LGPD/GDPR/CCPA/POPIA) -->
    @include('components.layout.cookie-consent')

    {{-- SweetAlert2 flash toasts (contact form, etc.) --}}
    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({ icon: 'success', title: 'Sucesso', text: @json(session('success')), confirmButtonColor: '#039855', timer: 5000, timerProgressBar: true });
        });
    </script>
    @endif
    @if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({ icon: 'error', title: 'Erro', text: @json(session('error')), confirmButtonColor: '#dc2626' });
        });
    </script>
    @endif

    @stack('scripts')
</body>
</html>
