<footer class="bg-gray-900 dark:bg-gray-950 text-gray-300 mt-auto" role="contentinfo">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Brand -->
            <div class="col-span-1 md:col-span-2">
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="w-9 h-9 bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-white text-sm"></i>
                    </div>
                    <span class="text-xl font-bold text-white">Finestro</span>
                </div>
                <p class="text-gray-400 mb-4 max-w-md text-sm leading-relaxed">
                    Gerencie suas finanças pessoais de forma simples, inteligente e segura. Controle carteiras, transações e metas financeiras em um só lugar.
                </p>
                <div class="flex space-x-3">
                    <a href="#" class="w-9 h-9 rounded-lg bg-gray-800 hover:bg-primary-600 flex items-center justify-center text-gray-400 hover:text-white transition" title="Facebook" aria-label="Facebook">
                        <i class="fab fa-facebook text-sm"></i>
                    </a>
                    <a href="#" class="w-9 h-9 rounded-lg bg-gray-800 hover:bg-primary-600 flex items-center justify-center text-gray-400 hover:text-white transition" title="Instagram" aria-label="Instagram">
                        <i class="fab fa-instagram text-sm"></i>
                    </a>
                    <a href="#" class="w-9 h-9 rounded-lg bg-gray-800 hover:bg-primary-600 flex items-center justify-center text-gray-400 hover:text-white transition" title="LinkedIn" aria-label="LinkedIn">
                        <i class="fab fa-linkedin text-sm"></i>
                    </a>
                </div>
            </div>

            <!-- Links -->
            <div>
                <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Links Úteis</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('home') }}#inicio" class="text-gray-400 hover:text-white transition text-sm">Home</a></li>
                    <li><a href="{{ route('home') }}#sobre" class="text-gray-400 hover:text-white transition text-sm">Sobre</a></li>
                    <li><a href="{{ route('home') }}#servicos" class="text-gray-400 hover:text-white transition text-sm">Serviços</a></li>
                    <li><a href="{{ route('home') }}#ferramentas" class="text-gray-400 hover:text-white transition text-sm">Ferramentas</a></li>
                    <li><a href="{{ route('home') }}#contato" class="text-gray-400 hover:text-white transition text-sm">Contato</a></li>
                </ul>
            </div>

            <!-- Legal -->
            <div>
                <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Legal</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('privacy') }}" class="text-gray-400 hover:text-white transition text-sm">Política de Privacidade</a></li>
                    <li><a href="{{ route('terms') }}" class="text-gray-400 hover:text-white transition text-sm">Termos de Uso</a></li>
                    <li><a href="{{ route('privacy') }}" class="text-gray-400 hover:text-white transition text-sm">LGPD</a></li>
                </ul>
            </div>
        </div>

        <!-- Bottom -->
        <div class="border-t border-gray-800 mt-10 pt-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-gray-500 text-sm">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
                </p>
                <div class="text-center md:text-right">
                    <p class="text-gray-400 text-sm">
                        Sistema desenvolvido por <strong class="text-gray-200">HOTWYL</strong> | <strong class="text-gray-200">WILLFROMBRASIL</strong>
                    </p>
                    <a href="https://www.3wonline.com.br" target="_blank" rel="noopener" class="text-xs text-gray-500 hover:text-primary-400 transition mt-1 inline-block">
                        <i class="fas fa-globe mr-1"></i> Grupo 3W Online - Sites e Sistemas Personalizados
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>
