<!DOCTYPE html>
<html lang="pt-BR" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', mobileMenu: false }" :class="{ 'dark': darkMode }">
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

    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
    @include('components.layout.analytics')
    @stack('styles')
</head>
<body class="min-h-screen flex flex-col bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-300 font-sans">
    @include('components.layout.gtm-noscript')

    <!-- Loading Overlay -->
    @include('components.ui.loading-overlay')

    <!-- Navigation -->
    <nav class="bg-white/90 dark:bg-gray-900/90 backdrop-blur-md border-b border-gray-200/80 dark:border-gray-700/80 sticky top-0 z-50" role="navigation" aria-label="Navegação principal">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 brand-logo">
                        <div class="w-9 h-9 bg-gradient-to-br from-primary-600 to-primary-700 rounded-lg flex items-center justify-center shadow-sm">
                            <i class="fas fa-chart-line text-white text-sm"></i>
                        </div>
                        <span class="text-xl font-bold text-gray-900 dark:text-white">Finestro</span>
                    </a>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-1">
                    <a href="{{ route('home') }}#inicio" class="px-3 py-2 rounded-lg text-sm font-medium transition text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-50 dark:hover:bg-gray-800">Home</a>
                    <a href="{{ route('home') }}#sobre" class="px-3 py-2 rounded-lg text-sm font-medium transition text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-50 dark:hover:bg-gray-800">Sobre</a>
                    <a href="{{ route('home') }}#servicos" class="px-3 py-2 rounded-lg text-sm font-medium transition text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-50 dark:hover:bg-gray-800">Serviços</a>
                    <a href="{{ route('home') }}#ferramentas" class="px-3 py-2 rounded-lg text-sm font-medium transition text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-50 dark:hover:bg-gray-800">Ferramentas</a>
                    <a href="{{ route('home') }}#contato" class="px-3 py-2 rounded-lg text-sm font-medium transition text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-50 dark:hover:bg-gray-800">Contato</a>

                    <div class="w-px h-6 bg-gray-200 dark:bg-gray-700 mx-2"></div>

                    <!-- Theme Toggle -->
                    <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                        class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                        title="Alternar tema claro/escuro" aria-label="Alternar tema">
                        <i x-show="!darkMode" class="fas fa-moon"></i>
                        <i x-show="darkMode" class="fas fa-sun"></i>
                    </button>

                    @auth
                        <a href="{{ route('dashboard') }}" class="ml-2 inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white text-sm font-medium rounded-lg transition shadow-sm" data-loading="Carregando...">
                            <i class="fas fa-tachometer-alt mr-1.5"></i> Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="ml-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-primary-600 transition">Entrar</a>
                        <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white text-sm font-medium rounded-lg transition shadow-sm" data-loading="Carregando...">Criar Conta</a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button @click="mobileMenu = !mobileMenu" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition" aria-label="Menu">
                        <i x-show="!mobileMenu" class="fas fa-bars text-xl"></i>
                        <i x-show="mobileMenu" class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenu" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
            class="md:hidden border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div class="px-4 py-3 space-y-1" @click="mobileMenu = false">
                <a href="{{ route('home') }}#inicio" class="block px-3 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 font-medium text-sm">Home</a>
                <a href="{{ route('home') }}#sobre" class="block px-3 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 font-medium text-sm">Sobre</a>
                <a href="{{ route('home') }}#servicos" class="block px-3 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 font-medium text-sm">Serviços</a>
                <a href="{{ route('home') }}#ferramentas" class="block px-3 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 font-medium text-sm">Ferramentas</a>
                <a href="{{ route('home') }}#contato" class="block px-3 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 font-medium text-sm">Contato</a>
                <hr class="border-gray-200 dark:border-gray-700 my-2">
                <button type="button" @click.stop="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                    class="flex items-center w-full px-3 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 font-medium text-sm text-gray-700 dark:text-gray-300"
                    aria-label="Alternar tema claro/escuro">
                    <i x-show="!darkMode" class="fas fa-moon w-5 mr-2"></i>
                    <i x-show="darkMode" class="fas fa-sun w-5 mr-2"></i>
                    <span x-text="darkMode ? 'Tema Claro' : 'Tema Escuro'"></span>
                </button>
                <hr class="border-gray-200 dark:border-gray-700 my-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="block px-3 py-2.5 rounded-lg bg-gradient-to-r from-primary-600 to-primary-700 text-white text-center font-medium text-sm">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="block px-3 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 font-medium text-sm">Entrar</a>
                    <a href="{{ route('register') }}" class="block px-3 py-2.5 rounded-lg bg-gradient-to-r from-primary-600 to-primary-700 text-white text-center font-medium text-sm">Criar Conta</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    @include('components.layout.footer')

    <!-- Cookie Consent (LGPD/GDPR/CCPA/POPIA) -->
    @include('components.layout.cookie-consent')

    {{-- SweetAlert2 flash toasts (contact form, etc.) --}}
    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({ icon: 'success', title: 'Sucesso', text: @json(session('success')), confirmButtonColor: '#2563eb', timer: 5000, timerProgressBar: true });
        });
    </script>
    @endif
    @if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({ icon: 'error', title: 'Erro', text: @json(session('error')), confirmButtonColor: '#2563eb' });
        });
    </script>
    @endif

    @stack('scripts')
</body>
</html>
