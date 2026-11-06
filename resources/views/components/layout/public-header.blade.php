<header role="banner" x-data="{ mobileMenu: false }" @keydown.escape.window="mobileMenu = false" class="bg-white/90 dark:bg-surface-900/90 backdrop-blur-md border-b border-surface-200/80 dark:border-surface-700/80 sticky top-0 z-50">
    <nav role="navigation" aria-label="Navegação principal">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <x-ui.logo :href="route('home')" />
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-1">
                    <a href="{{ route('home') }}#inicio" class="px-3 py-2 rounded-lg text-sm font-medium transition text-surface-700 dark:text-surface-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-surface-50 dark:hover:bg-surface-800">Home</a>
                    <a href="{{ route('home') }}#sobre" class="px-3 py-2 rounded-lg text-sm font-medium transition text-surface-700 dark:text-surface-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-surface-50 dark:hover:bg-surface-800">Sobre</a>
                    <a href="{{ route('home') }}#servicos" class="px-3 py-2 rounded-lg text-sm font-medium transition text-surface-700 dark:text-surface-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-surface-50 dark:hover:bg-surface-800">Serviços</a>
                    <a href="{{ route('home') }}#ferramentas" class="px-3 py-2 rounded-lg text-sm font-medium transition text-surface-700 dark:text-surface-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-surface-50 dark:hover:bg-surface-800">Ferramentas</a>
                    <a href="{{ route('faq') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition text-surface-700 dark:text-surface-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-surface-50 dark:hover:bg-surface-800" title="Central de Ajuda: perguntas frequentes">Ajuda</a>
                    <a href="{{ route('contact') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition text-surface-700 dark:text-surface-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-surface-50 dark:hover:bg-surface-800" @if(request()->routeIs('contact')) aria-current="page" @endif>Contato</a>

                    <div class="w-px h-6 bg-surface-200 dark:bg-surface-700 mx-2"></div>

                    <!-- Theme Toggle -->
                    <button type="button" @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                        class="p-2 rounded-lg text-surface-500 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800 transition"
                        title="Alternar tema claro/escuro" aria-label="Alternar tema">
                        <i x-show="!darkMode" class="fas fa-moon"></i>
                        <i x-show="darkMode" class="fas fa-sun"></i>
                    </button>

                    @auth
                        <a href="{{ route('dashboard') }}" class="ml-2 inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white text-sm font-medium rounded-lg transition shadow-sm" data-loading="Carregando...">
                            <i class="fas fa-tachometer-alt mr-1.5"></i> Painel
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="ml-2 px-3 py-2 text-sm font-medium text-surface-700 dark:text-surface-300 hover:text-primary-600 transition">Entrar</a>
                        <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white text-sm font-medium rounded-lg transition shadow-sm" data-loading="Carregando...">Criar Conta</a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button type="button" @click="mobileMenu = !mobileMenu" class="p-2 rounded-lg text-surface-500 hover:bg-surface-100 dark:hover:bg-surface-800 transition" aria-label="Menu" :aria-expanded="mobileMenu.toString()" aria-controls="public-mobile-menu">
                        <i x-show="!mobileMenu" class="fas fa-bars text-xl"></i>
                        <i x-show="mobileMenu" class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="public-mobile-menu" x-show="mobileMenu" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
            class="md:hidden border-t border-surface-200 dark:border-surface-700 bg-white dark:bg-surface-900">
            <div class="px-4 py-3 space-y-1" @click="mobileMenu = false">
                <a href="{{ route('home') }}#inicio" class="block px-3 py-2.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 font-medium text-sm">Home</a>
                <a href="{{ route('home') }}#sobre" class="block px-3 py-2.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 font-medium text-sm">Sobre</a>
                <a href="{{ route('home') }}#servicos" class="block px-3 py-2.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 font-medium text-sm">Serviços</a>
                <a href="{{ route('home') }}#ferramentas" class="block px-3 py-2.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 font-medium text-sm">Ferramentas</a>
                <a href="{{ route('faq') }}" class="block px-3 py-2.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 font-medium text-sm">Ajuda</a>
                <a href="{{ route('contact') }}" class="block px-3 py-2.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 font-medium text-sm">Contato</a>
                <hr class="border-surface-200 dark:border-surface-700 my-2">
                <button type="button" @click.stop="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                    class="flex items-center w-full px-3 py-2.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 font-medium text-sm text-surface-700 dark:text-surface-300"
                    aria-label="Alternar tema claro/escuro">
                    <i x-show="!darkMode" class="fas fa-moon w-5 mr-2"></i>
                    <i x-show="darkMode" class="fas fa-sun w-5 mr-2"></i>
                    <span x-text="darkMode ? 'Tema Claro' : 'Tema Escuro'"></span>
                </button>
                <hr class="border-surface-200 dark:border-surface-700 my-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="block px-3 py-2.5 rounded-lg bg-gradient-to-r from-primary-600 to-primary-700 text-white text-center font-medium text-sm">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="block px-3 py-2.5 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 font-medium text-sm">Entrar</a>
                    <a href="{{ route('register') }}" class="block px-3 py-2.5 rounded-lg bg-gradient-to-r from-primary-600 to-primary-700 text-white text-center font-medium text-sm">Criar Conta</a>
                @endauth
            </div>
        </div>
    </nav>
</header>
