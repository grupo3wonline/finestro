<footer class="bg-surface-900 dark:bg-surface-950 text-surface-300 mt-auto" role="contentinfo">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Brand -->
            <div class="col-span-1 md:col-span-2">
                <div class="mb-4">
                    <x-ui.logo textClass="text-white" />
                </div>
                <p class="text-surface-400 mb-4 max-w-md text-sm leading-relaxed">
                    Gerencie suas finanças pessoais de forma simples, inteligente e segura. Controle carteiras, transações e metas financeiras em um só lugar.
                </p>
                @php
                    // Only profiles with a configured URL are shown (no dead "#" links).
                    $socialLinks = collect([
                        ['key' => 'facebook', 'label' => 'Facebook', 'icon' => 'fa-facebook'],
                        ['key' => 'instagram', 'label' => 'Instagram', 'icon' => 'fa-instagram'],
                        ['key' => 'linkedin', 'label' => 'LinkedIn', 'icon' => 'fa-linkedin'],
                    ])->map(fn (array $s): array => $s + ['url' => config('marketing.social.'.$s['key'])])
                        ->filter(fn (array $s): bool => filled($s['url']));
                @endphp
                @if($socialLinks->isNotEmpty())
                <div class="flex space-x-3">
                    @foreach($socialLinks as $social)
                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="w-9 h-9 rounded-lg bg-surface-800 hover:bg-primary-600 flex items-center justify-center text-surface-400 hover:text-white transition" title="{{ $social['label'] }}" aria-label="{{ $social['label'] }}">
                        <i class="fab {{ $social['icon'] }} text-sm" aria-hidden="true"></i>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Links -->
            <div>
                <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Links Úteis</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('home') }}#inicio" class="text-surface-400 hover:text-white transition text-sm">Home</a></li>
                    <li><a href="{{ route('home') }}#sobre" class="text-surface-400 hover:text-white transition text-sm">Sobre</a></li>
                    <li><a href="{{ route('home') }}#servicos" class="text-surface-400 hover:text-white transition text-sm">Serviços</a></li>
                    <li><a href="{{ route('home') }}#ferramentas" class="text-surface-400 hover:text-white transition text-sm">Ferramentas</a></li>
                    <li><a href="{{ route('faq') }}" class="text-surface-400 hover:text-white transition text-sm">Central de Ajuda</a></li>
                    <li><a href="{{ route('contact') }}" class="text-surface-400 hover:text-white transition text-sm">Contato</a></li>
                </ul>
            </div>

            <!-- Legal -->
            <div>
                <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Legal</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('privacy') }}" class="text-surface-400 hover:text-white transition text-sm">Política de Privacidade</a></li>
                    <li><a href="{{ route('terms') }}" class="text-surface-400 hover:text-white transition text-sm">Termos de Uso</a></li>
                    <li><a href="{{ route('cookies') }}" class="text-surface-400 hover:text-white transition text-sm">Política de Cookies</a></li>
                    <li><a href="{{ route('privacy') }}#seus-direitos" class="text-surface-400 hover:text-white transition text-sm">Seus direitos (LGPD)</a></li>
                    <li><a href="{{ route('cookies') }}#nao-vender" class="text-surface-400 hover:text-white transition text-sm">Não venda ou compartilhe minhas informações</a></li>
                    <li>
                        <button type="button" @click="$dispatch('open-cookie-preferences')" class="text-surface-400 hover:text-white transition text-sm text-left" title="Escolha quais cookies opcionais podem ser usados">
                            Preferências de cookies
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bottom -->
        <div class="border-t border-surface-800 mt-10 pt-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-surface-500 text-sm">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
                </p>
                <div class="text-center md:text-right">
                    <p class="text-surface-300 text-sm font-medium">Sistema desenvolvido pelo desenvolvedor HOTWYL | WILLFROMBRASIL</p>
                    <a href="https://www.3wonline.com.br" target="_blank" rel="noopener" class="text-xs text-surface-500 hover:text-primary-400 transition mt-1 inline-block">
                        <i class="fas fa-globe mr-1"></i> Grupo 3W Online - Sites e Sistemas Personalizados
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>
