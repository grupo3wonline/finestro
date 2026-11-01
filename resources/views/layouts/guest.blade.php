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

    <title>{{ config('app.name') }} - @yield('title', 'Acesso')</title>

    <!-- Preconnect -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <!-- Inter (body) + Fraunces (financial display) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">

    <!-- Font Awesome (non-blocking) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></noscript>
    <!-- SweetAlert2 (deferred; every Swal call runs on DOMContentLoaded or in an event handler) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
    @include('components.layout.analytics')
</head>
<body class="min-h-screen bg-gradient-to-br from-surface-50 via-surface-50 to-primary-50/40 dark:from-surface-950 dark:via-surface-950 dark:to-surface-900 flex flex-col transition-colors duration-300">
    @include('components.layout.gtm-noscript')

    <!-- Loading Overlay -->
    @include('components.ui.loading-overlay')

    <!-- Header / Navigation -->
    @include('components.layout.public-header')

    <main class="flex-grow flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <x-ui.logo :href="route('home')" size="lg" class="justify-center" />
                <p class="text-sm text-surface-500 dark:text-surface-300 mt-3">Gestão financeira pessoal inteligente</p>
            </div>

            <!-- Card -->
            <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-elevated border border-surface-100 dark:border-surface-800 p-8">
                @yield('content')
            </div>

            <!-- Theme Toggle -->
            <div class="text-center mt-6">
                <button type="button" @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                    class="p-2 rounded-xl text-surface-400 dark:text-surface-500 hover:text-surface-600 dark:hover:text-surface-300 hover:bg-surface-100 dark:hover:bg-surface-800 transition"
                    title="Alternar tema claro/escuro" aria-label="Alternar tema claro/escuro">
                    <i x-show="!darkMode" class="fas fa-moon"></i>
                    <i x-show="darkMode" class="fas fa-sun"></i>
                </button>
            </div>
        </div>
    </main>

    <!-- Grupo 3W Online CTA -->
    <x-cta.agency variant="auth" />

    <!-- Footer -->
    @include('components.layout.footer')

    <!-- Cookie Consent (LGPD/GDPR/CCPA/POPIA) -->
    @include('components.layout.cookie-consent')
</body>
</html>
