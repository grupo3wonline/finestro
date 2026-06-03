<header class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-md border-b border-gray-200/80 dark:border-gray-700/80 sticky top-0 z-50" x-data="{ open: false, profileMenu: false }" role="banner">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 brand-logo" data-loading="Carregando...">
                    <div class="w-9 h-9 bg-gradient-to-br from-primary-600 to-primary-700 rounded-lg flex items-center justify-center shadow-sm">
                        <i class="fas fa-chart-line text-white text-sm"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-900 dark:text-white">Finestro</span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center space-x-1" role="navigation" aria-label="Menu principal">
                <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/60' }}" data-loading="Carregando...">
                    <i class="fas fa-tachometer-alt mr-1.5"></i> Dashboard
                </a>
                <a href="{{ route('wallets.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('wallets.*') ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/60' }}" data-loading="Carregando...">
                    <i class="fas fa-wallet mr-1.5"></i> Carteiras
                </a>
                <a href="{{ route('transactions.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('transactions.*') ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/60' }}" data-loading="Carregando...">
                    <i class="fas fa-exchange-alt mr-1.5"></i> Transações
                </a>
                <a href="{{ route('goals.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('goals.*') ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/60' }}" data-loading="Carregando...">
                    <i class="fas fa-bullseye mr-1.5"></i> Metas
                </a>
                <a href="{{ route('simulators.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('simulators.*') ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/60' }}" data-loading="Carregando...">
                    <i class="fas fa-calculator mr-1.5"></i> Simuladores
                </a>
                <a href="{{ route('reports.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('reports.*') ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/60' }}" data-loading="Carregando...">
                    <i class="fas fa-chart-bar mr-1.5"></i> Relatórios
                </a>
                @if(Auth::user()->hasFeature('ai_insights'))
                <a href="{{ route('ai.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('ai.*') ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/60' }}" data-loading="Carregando...">
                    <i class="fas fa-robot mr-1.5"></i> IA
                </a>
                @endif
            </nav>

            <!-- Right Side -->
            <div class="flex items-center space-x-2">
                <!-- Theme Toggle -->
                <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                    class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                    title="Alternar tema claro/escuro" aria-label="Alternar tema">
                    <i x-show="!darkMode" class="fas fa-moon"></i>
                    <i x-show="darkMode" class="fas fa-sun"></i>
                </button>

                <!-- Help Button -->
                <button onclick="Swal.fire({title: 'Central de Ajuda', html: '<div class=\'text-left text-sm\'><p class=\'mb-3\'>Navegue pelo menu para acessar suas carteiras, transações e metas financeiras.</p><p class=\'mb-3\'><strong>Atalhos:</strong></p><ul class=\'list-disc pl-4 space-y-1\'><li>Use o tema escuro para reduzir o cansaço visual</li><li>Clique no seu avatar para acessar configurações</li><li>Crie categorias para organizar melhor suas transações</li></ul></div>', icon: 'info', confirmButtonColor: '#1a6df5', confirmButtonText: 'Entendi', customClass: {popup: 'rounded-xl'}})"
                    class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                    title="Ajuda" aria-label="Ajuda">
                    <i class="fas fa-question-circle"></i>
                </button>

                <!-- Profile Dropdown -->
                <div class="relative" @click.away="profileMenu = false">
                    <button @click="profileMenu = !profileMenu" class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition" aria-label="Menu do perfil" :aria-expanded="profileMenu.toString()">
                        <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-700 rounded-lg flex items-center justify-center shadow-sm">
                            <span class="text-white text-sm font-semibold">{{ substr(Auth::user()->name ?? 'U', 0, 1) }}</span>
                        </div>
                        <span class="hidden lg:block text-sm font-medium text-gray-700 dark:text-gray-300 max-w-[120px] truncate">{{ Auth::user()->name ?? 'Usuário' }}</span>
                        <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': profileMenu }"></i>
                    </button>

                    <div x-show="profileMenu" x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-52 bg-white dark:bg-gray-800 rounded-xl shadow-elevated border border-gray-100 dark:border-gray-700/60 py-1.5 z-50">
                        <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700/60 mb-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ Auth::user()->name ?? 'Usuário' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email ?? '' }}</p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition">
                            <i class="fas fa-user w-5 text-gray-400"></i> Meu Perfil
                        </a>
                        <a href="{{ route('categories.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition">
                            <i class="fas fa-tags w-5 text-gray-400"></i> Categorias
                        </a>
                        <a href="{{ route('subscription.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition">
                            <i class="fas fa-crown w-5 text-yellow-500"></i> Assinatura
                        </a>
                        <a href="{{ route('settings.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition">
                            <i class="fas fa-cog w-5 text-gray-400"></i> Configurações
                        </a>
                        <hr class="border-gray-100 dark:border-gray-700/60 my-1.5">
                        <form method="POST" action="{{ route('logout') }}" data-no-loading>
                            @csrf
                            <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/10 transition">
                                <i class="fas fa-sign-out-alt w-5"></i> Sair
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <button @click="open = !open" class="md:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition" aria-label="Menu de navegação">
                    <i x-show="!open" class="fas fa-bars"></i>
                    <i x-show="open" class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <div x-show="open" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="md:hidden border-t border-gray-200 dark:border-gray-700">
        <div class="px-4 py-3 space-y-1">
            <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition {{ request()->routeIs('dashboard') ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : '' }}">
                <i class="fas fa-tachometer-alt w-6 text-center"></i> Dashboard
            </a>
            <a href="{{ route('wallets.index') }}" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition {{ request()->routeIs('wallets.*') ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : '' }}">
                <i class="fas fa-wallet w-6 text-center"></i> Carteiras
            </a>
            <a href="{{ route('transactions.index') }}" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition {{ request()->routeIs('transactions.*') ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : '' }}">
                <i class="fas fa-exchange-alt w-6 text-center"></i> Transações
            </a>
            <a href="{{ route('goals.index') }}" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition {{ request()->routeIs('goals.*') ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : '' }}">
                <i class="fas fa-bullseye w-6 text-center"></i> Metas
            </a>
            <a href="{{ route('simulators.index') }}" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition {{ request()->routeIs('simulators.*') ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : '' }}">
                <i class="fas fa-calculator w-6 text-center"></i> Simuladores
            </a>
            <a href="{{ route('reports.index') }}" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition {{ request()->routeIs('reports.*') ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : '' }}">
                <i class="fas fa-chart-bar w-6 text-center"></i> Relatórios
            </a>
            <hr class="border-gray-200 dark:border-gray-700 my-2">
            <a href="{{ route('categories.index') }}" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <i class="fas fa-tags w-6 text-center"></i> Categorias
            </a>
            <a href="{{ route('subscription.index') }}" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <i class="fas fa-crown w-6 text-center text-yellow-500"></i> Assinatura
            </a>
            <a href="{{ route('settings.index') }}" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <i class="fas fa-cog w-6 text-center"></i> Configurações
            </a>
        </div>
    </div>
</header>
