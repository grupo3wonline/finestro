@extends('layouts.landing')

@section('title', 'Política de Privacidade')
@section('meta_description', 'Política de Privacidade do Finestro: quais dados tratamos, por quê, com quem compartilhamos e como exercer seus direitos pela LGPD, GDPR, CCPA/CPRA e POPIA.')

@php
    $controller = config('legal.controller');
    $isPlaceholder = fn (?string $value): bool => str_starts_with((string) $value, '[PREENCHER');
    $dpoEmail = (string) config('privacy.dpo_email');
    $updatedAt = \Illuminate\Support\Carbon::parse((string) config('legal.policy_updated_at'))->format('d/m/Y');
    $h2 = 'text-xl font-semibold text-surface-900 dark:text-white scroll-mt-24';
    $h3 = 'text-base font-semibold text-surface-900 dark:text-white mt-4';
    $link = 'text-primary-600 dark:text-primary-400 hover:underline';
@endphp

@section('content')
<section class="py-12 sm:py-16 bg-surface-50 dark:bg-surface-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <x-ui.breadcrumb :items="[['label' => 'Política de Privacidade']]" />
        </div>

        <article class="bg-white dark:bg-surface-950 rounded-2xl p-6 sm:p-8 md:p-12 shadow-sm border border-surface-100 dark:border-surface-800">
            <h1 class="text-3xl font-bold text-surface-900 dark:text-white mb-3">Política de Privacidade</h1>
            <p class="text-sm text-surface-500 dark:text-surface-400 mb-6">
                Versão {{ config('legal.policy_version') }} · Última atualização: {{ $updatedAt }}
            </p>

            <x-ui.tip icon="fa-circle-info" class="mb-8">
                Resumo: usamos seus dados só para prestar o serviço de gestão financeira, cobrar o plano Profissional e manter sua conta segura.
                Não vendemos nem compartilhamos dados pessoais para publicidade. Você pode baixar, corrigir ou excluir seus dados em
                @auth<a href="{{ route('settings.privacy') }}" class="{{ $link }}">Configurações &gt; Privacidade</a>@else Configurações &gt; Privacidade (após entrar)@endauth.
            </x-ui.tip>

            <nav aria-label="Índice da política" class="mb-10 p-4 rounded-xl bg-surface-50 dark:bg-surface-900 text-sm">
                <p class="font-semibold text-surface-900 dark:text-white mb-2">Nesta página</p>
                <ol class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 list-decimal pl-5 text-surface-600 dark:text-surface-300">
                    <li><a href="#controlador" class="{{ $link }}">Quem é o controlador</a></li>
                    <li><a href="#encarregado" class="{{ $link }}">Encarregado (DPO)</a></li>
                    <li><a href="#dados-coletados" class="{{ $link }}">Dados que tratamos</a></li>
                    <li><a href="#finalidades" class="{{ $link }}">Finalidades e bases legais</a></li>
                    <li><a href="#compartilhamento" class="{{ $link }}">Compartilhamento</a></li>
                    <li><a href="#transferencias" class="{{ $link }}">Transferências internacionais</a></li>
                    <li><a href="#retencao" class="{{ $link }}">Por quanto tempo guardamos</a></li>
                    <li><a href="#seus-direitos" class="{{ $link }}">Seus direitos</a></li>
                    <li><a href="#como-exercer" class="{{ $link }}">Como exercer seus direitos</a></li>
                    <li><a href="#ccpa" class="{{ $link }}">Aviso para residentes da Califórnia</a></li>
                    <li><a href="#criancas" class="{{ $link }}">Crianças e adolescentes</a></li>
                    <li><a href="#seguranca" class="{{ $link }}">Segurança</a></li>
                    <li><a href="#cookies" class="{{ $link }}">Cookies</a></li>
                    <li><a href="#alteracoes" class="{{ $link }}">Alterações desta política</a></li>
                </ol>
            </nav>

            <div class="space-y-6 text-surface-700 dark:text-surface-300 leading-relaxed">
                <p>
                    Esta política explica como o Finestro trata dados pessoais conforme a Lei Geral de Proteção de Dados (LGPD, Lei nº 13.709/2018),
                    o Regulamento Geral sobre a Proteção de Dados da União Europeia (GDPR, Regulamento 2016/679),
                    a lei da Califórnia (CCPA, alterada pela CPRA) e a lei sul-africana Protection of Personal Information Act (POPIA, 2013).
                </p>

                <h2 id="controlador" class="{{ $h2 }}">1. Quem é o controlador</h2>
                <p>O controlador (responsável) pelos seus dados no Finestro é:</p>
                <ul class="list-disc pl-6 space-y-1">
                    @foreach(['legal_name' => 'Razão social', 'cnpj' => 'CNPJ', 'address' => 'Endereço'] as $key => $label)
                        <li>
                            {{ $label }}: <span class="font-medium">{{ $controller[$key] ?? '' }}</span>
                            @if($isPlaceholder($controller[$key] ?? ''))
                                <x-ui.badge variant="warning" icon="fa-triangle-exclamation">dado pendente de preenchimento</x-ui.badge>
                            @endif
                        </li>
                    @endforeach
                    <li>Sistema desenvolvido pelo Grupo 3W Online (<a href="https://www.3wonline.com.br" target="_blank" rel="noopener" class="{{ $link }}">www.3wonline.com.br</a>).</li>
                </ul>

                <h2 id="encarregado" class="{{ $h2 }}">2. Encarregado pelo tratamento de dados (DPO)</h2>
                <p>
                    Para qualquer assunto de privacidade, fale com o encarregado (LGPD art. 41), que também atua como Data Protection Officer (GDPR)
                    e Information Officer (POPIA): <a href="mailto:{{ $dpoEmail }}" class="{{ $link }}">{{ $dpoEmail }}</a>.
                </p>

                <h2 id="dados-coletados" class="{{ $h2 }}">3. Dados que tratamos</h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Conta:</strong> nome, nome de usuário, e-mail, senha (guardada apenas como hash, nunca em texto), data de verificação do e-mail e versão/data de aceite dos Termos e desta Política.</li>
                    <li><strong>Registros financeiros que você cadastra:</strong> carteiras, transações (descrição, valor, data, situação, forma de pagamento, observações), categorias, metas, orçamentos e transações recorrentes.</li>
                    <li><strong>Comprovantes:</strong> arquivos que você anexa às transações (imagens ou PDF).</li>
                    <li><strong>Pagamentos (plano Profissional):</strong> identificadores de cliente e de assinatura no Stripe, plano, situação, período e histórico de cobranças. <em>Não recebemos nem guardamos dados do cartão</em>: eles são digitados diretamente no Stripe.</li>
                    <li><strong>Inteligência artificial (opcional):</strong> se você configurar uma chave própria de Groq, Google Gemini ou OpenRouter, guardamos a chave criptografada. Ao pedir uma análise, enviamos ao provedor escolhido um resumo financeiro (saldos, receitas e despesas do mês, metas ativas e principais categorias de despesa), sem nome ou e-mail.</li>
                    <li><strong>Registros de atividade e segurança:</strong> entradas, tentativas de acesso, alterações em seus registros e o endereço IP de cada evento.</li>
                    <li><strong>Cookies e armazenamento local:</strong> ver a <a href="{{ route('cookies') }}" class="{{ $link }}">Política de Cookies</a>.</li>
                </ul>
                <p>Não tratamos dados sensíveis (LGPD art. 5º II, GDPR art. 9) e pedimos que você não os inclua em descrições ou observações.</p>

                <h2 id="finalidades" class="{{ $h2 }}">4. Finalidades e bases legais</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border border-surface-200 dark:border-surface-800">
                        <caption class="sr-only">Finalidade do tratamento e base legal</caption>
                        <thead class="bg-surface-50 dark:bg-surface-900">
                            <tr class="text-left">
                                <th scope="col" class="p-2 font-semibold">Finalidade</th>
                                <th scope="col" class="p-2 font-semibold">LGPD (art. 7º)</th>
                                <th scope="col" class="p-2 font-semibold">GDPR (art. 6)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-200 dark:divide-surface-800">
                            <tr><td class="p-2">Criar e manter sua conta, guardar e exibir seus registros financeiros</td><td class="p-2">Execução de contrato (V)</td><td class="p-2">Contrato (1)(b)</td></tr>
                            <tr><td class="p-2">Cobrar o plano Profissional e emitir registros de pagamento</td><td class="p-2">Execução de contrato (V) e obrigação legal (II)</td><td class="p-2">Contrato (1)(b) e obrigação legal (1)(c)</td></tr>
                            <tr><td class="p-2">Segurança: registros de acesso, prevenção a fraudes e abusos</td><td class="p-2">Obrigação legal (II, Marco Civil da Internet art. 15) e legítimo interesse (IX)</td><td class="p-2">Interesse legítimo (1)(f)</td></tr>
                            <tr><td class="p-2">E-mails do serviço (confirmação de cadastro, senha, avisos da conta)</td><td class="p-2">Execução de contrato (V)</td><td class="p-2">Contrato (1)(b)</td></tr>
                            <tr><td class="p-2">Análises com IA quando você as solicita</td><td class="p-2">Consentimento (I)</td><td class="p-2">Consentimento (1)(a)</td></tr>
                            <tr><td class="p-2">Cookies de análise e de marketing</td><td class="p-2">Consentimento (I)</td><td class="p-2">Consentimento (1)(a)</td></tr>
                        </tbody>
                    </table>
                </div>
                <p>Não tomamos decisões exclusivamente automatizadas que produzam efeitos jurídicos sobre você. As análises de IA são sugestões que você pede e decide se segue.</p>

                <h2 id="compartilhamento" class="{{ $h2 }}">5. Compartilhamento</h2>
                <p>Seus registros financeiros são visíveis apenas para você e para quem você convidar a uma carteira compartilhada. Compartilhamos dados somente com operadores que nos ajudam a prestar o serviço:</p>
                <ul class="list-disc pl-6 space-y-1">
                    <li><strong>Stripe</strong>: processamento de pagamentos do plano Profissional.</li>
                    <li><strong>Provedor de e-mail</strong>: envio das mensagens da conta.</li>
                    <li><strong>Provedores de IA</strong> (Groq, Google Gemini ou OpenRouter): apenas quando você configura sua chave e pede uma análise.</li>
                    <li><strong>Hospedagem</strong>: servidores onde o sistema e o banco de dados funcionam.</li>
                    <li><strong>Google e Meta</strong>: somente se você permitir cookies de análise ou de marketing.</li>
                    <li><strong>Autoridades</strong>: quando houver ordem judicial ou obrigação legal.</li>
                </ul>

                <h2 id="transferencias" class="{{ $h2 }}">6. Transferências internacionais</h2>
                <p>
                    Stripe, Google, Meta e os provedores de IA podem tratar dados fora do Brasil (por exemplo, nos Estados Unidos).
                    Essas transferências seguem a LGPD art. 33 (cláusulas contratuais e garantias do operador) e, para titulares no Espaço Econômico Europeu,
                    as cláusulas-padrão contratuais aprovadas pela Comissão Europeia (GDPR art. 46). Para titulares na África do Sul, aplicam-se as condições da POPIA seção 72.
                </p>

                <h2 id="retencao" class="{{ $h2 }}">7. Por quanto tempo guardamos</h2>
                <ul class="list-disc pl-6 space-y-1">
                    <li><strong>Conta e registros financeiros:</strong> enquanto a conta existir.</li>
                    <li><strong>Registro de atividade:</strong> {{ (int) config('activitylog.clean_after_days') }} dias; depois é apagado automaticamente.</li>
                    <li><strong>Arquivos de exportação de dados:</strong> {{ (int) config('privacy.export_ttl_hours') }} horas; depois o arquivo é apagado.</li>
                    <li><strong>Consentimentos de cookies:</strong> enquanto a conta existir, como prova da sua escolha; o cookie no navegador dura {{ (int) config('privacy.consent_lifetime_days') }} dias.</li>
                    <li><strong>Pagamentos:</strong> pelo prazo exigido pela legislação fiscal e contábil.</li>
                    <li>
                        <strong>Exclusão da conta:</strong> o acesso termina imediatamente, a assinatura é cancelada no Stripe e os dados são desativados (deixam de ser exibidos e usados).
                        A eliminação definitiva pode ser solicitada ao encarregado em <a href="mailto:{{ $dpoEmail }}" class="{{ $link }}">{{ $dpoEmail }}</a>,
                        exceto o que a lei obriga a manter.
                    </li>
                </ul>

                <h2 id="seus-direitos" class="{{ $h2 }}">8. Seus direitos</h2>
                <p>Os direitos abaixo valem para todos os usuários, independentemente do país. Não cobramos nada para exercê-los e você não sofre discriminação por isso.</p>

                <h3 class="{{ $h3 }}">LGPD (Brasil), art. 18</h3>
                <ul class="list-disc pl-6 space-y-1">
                    <li>Confirmação de que tratamos seus dados e acesso a eles.</li>
                    <li>Correção de dados incompletos, inexatos ou desatualizados.</li>
                    <li>Anonimização, bloqueio ou eliminação de dados desnecessários, excessivos ou tratados em desconformidade.</li>
                    <li>Portabilidade dos dados a outro fornecedor.</li>
                    <li>Eliminação dos dados tratados com seu consentimento.</li>
                    <li>Informação sobre com quem compartilhamos dados e sobre a possibilidade de não consentir e suas consequências.</li>
                    <li>Revogação do consentimento a qualquer momento.</li>
                    <li>Revisão de decisões automatizadas (art. 20) e petição à Autoridade Nacional de Proteção de Dados (ANPD).</li>
                </ul>

                <h3 class="{{ $h3 }}">GDPR (União Europeia), arts. 15 a 22</h3>
                <ul class="list-disc pl-6 space-y-1">
                    <li>Acesso (art. 15), retificação (art. 16) e apagamento (art. 17).</li>
                    <li>Limitação do tratamento (art. 18) e portabilidade (art. 20).</li>
                    <li>Oposição ao tratamento baseado em interesse legítimo (art. 21) e a não ficar sujeito a decisões exclusivamente automatizadas (art. 22).</li>
                    <li>Retirar o consentimento (art. 7(3)) e reclamar à autoridade de controle do seu país (art. 77).</li>
                </ul>

                <h3 class="{{ $h3 }}">CCPA/CPRA (Califórnia, EUA)</h3>
                <ul class="list-disc pl-6 space-y-1">
                    <li>Saber quais dados pessoais coletamos, usamos e divulgamos (right to know).</li>
                    <li>Excluir (right to delete) e corrigir (right to correct) seus dados.</li>
                    <li>Recusar a venda ou o compartilhamento de dados pessoais (right to opt-out) e limitar o uso de dados sensíveis (right to limit).</li>
                    <li>Não ser discriminado por exercer esses direitos.</li>
                </ul>

                <h3 class="{{ $h3 }}">POPIA (África do Sul), seções 23 a 25</h3>
                <ul class="list-disc pl-6 space-y-1">
                    <li>Saber se tratamos seus dados e obter acesso a eles (seção 23).</li>
                    <li>Pedir a correção ou a eliminação de dados inexatos, excessivos ou obtidos ilegalmente (seção 24), na forma da seção 25.</li>
                    <li>Opor-se ao tratamento (seção 11(3)) e reclamar ao Information Regulator.</li>
                </ul>

                <h2 id="como-exercer" class="{{ $h2 }}">9. Como exercer seus direitos</h2>
                <ul class="list-disc pl-6 space-y-1">
                    <li><strong>Em Configurações &gt; Privacidade</strong> (com login): baixe todos os seus dados (JSON + CSV), veja o histórico de consentimento, mude cookies de análise e marketing e exclua a conta.</li>
                    <li><strong>Em Editar perfil:</strong> corrija nome, usuário e e-mail.</li>
                    <li><strong>Por e-mail</strong> ao encarregado: <a href="mailto:{{ $dpoEmail }}" class="{{ $link }}">{{ $dpoEmail }}</a>. Podemos pedir informações para confirmar sua identidade. Respondemos em até 15 dias (LGPD art. 19) e no máximo em 30 dias (GDPR art. 12).</li>
                </ul>

                <h2 id="ccpa" class="{{ $h2 }}">10. Aviso para residentes da Califórnia (CCPA/CPRA)</h2>
                <p>
                    <strong>Não vendemos nem compartilhamos dados pessoais</strong> no sentido da CCPA/CPRA, e não usamos dados sensíveis para inferir características.
                    Se você permitir cookies de marketing, o Meta Pixel e o Google Ads podem receber dados de navegação; você pode desligar isso a qualquer momento em
                    <a href="{{ route('cookies') }}#nao-vender" class="{{ $link }}">Não venda ou compartilhe minhas informações</a>.
                    Respeitamos o sinal Global Privacy Control (GPC) do navegador como pedido de opt-out.
                </p>

                <h2 id="criancas" class="{{ $h2 }}">11. Crianças e adolescentes</h2>
                <p>O Finestro não é destinado a menores de 18 anos e não coletamos conscientemente dados deles. Se souber de uma conta de menor, avise o encarregado para a excluirmos.</p>

                <h2 id="seguranca" class="{{ $h2 }}">12. Segurança</h2>
                <p>
                    Senhas guardadas com hash, chaves de IA criptografadas, conexão HTTPS em produção, proteção contra CSRF e limite de tentativas de login,
                    acesso restrito para que cada usuário veja apenas os próprios dados e autenticação em dois fatores para administradores.
                    Nenhuma medida é infalível: se ocorrer um incidente de segurança relevante, avisaremos você e as autoridades nos prazos legais.
                </p>

                <h2 id="cookies" class="{{ $h2 }}">13. Cookies</h2>
                <p>
                    Usamos cookies necessários e, só com sua permissão, cookies de análise e de marketing. A lista completa está na
                    <a href="{{ route('cookies') }}" class="{{ $link }}">Política de Cookies</a>. Para mudar sua escolha, use
                    <button type="button" @click="$dispatch('open-cookie-preferences')" class="{{ $link }} font-medium">Preferências de cookies</button>.
                </p>

                <h2 id="alteracoes" class="{{ $h2 }}">14. Alterações desta política</h2>
                <p>
                    Esta é a versão {{ config('legal.policy_version') }}, de {{ $updatedAt }}. Quando ela mudar de forma relevante, mostraremos a nova versão
                    e pediremos seu aceite novamente ao entrar. Dúvidas: <a href="{{ route('contact') }}" class="{{ $link }}">Contato</a>
                    ou <a href="mailto:{{ $dpoEmail }}" class="{{ $link }}">{{ $dpoEmail }}</a>.
                </p>
            </div>
        </article>
    </div>
</section>
@endsection
