@extends('layouts.landing')

@section('title', 'Política de Privacidade')
@section('meta_description', 'Política de Privacidade do Finestro - Saiba como tratamos e protegemos seus dados pessoais em conformidade com a LGPD.')

@section('content')
<section class="py-16 bg-gray-50 dark:bg-gray-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8 text-sm" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
                <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
                <li class="text-gray-700 dark:text-gray-300 font-medium">Política de Privacidade</li>
            </ol>
        </nav>

        <div class="bg-white dark:bg-gray-900 rounded-xl p-8 md:p-12 shadow-sm border border-gray-200 dark:border-gray-700">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Política de Privacidade</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">Última atualização: {{ date('d/m/Y') }}</p>

            <div class="prose dark:prose-invert max-w-none space-y-6 text-gray-700 dark:text-gray-300">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">1. Introdução</h2>
                <p>O Finestro está comprometido com a proteção dos seus dados pessoais em conformidade com as principais legislações globais de privacidade, incluindo a Lei Geral de Proteção de Dados brasileira (LGPD - Lei nº 13.709/2018), o Regulamento Geral de Proteção de Dados europeu (GDPR), a lei da Califórnia (CCPA/CPRA) e a lei sul-africana (POPIA). Esta política descreve como coletamos, usamos, armazenamos e protegemos suas informações.</p>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">2. Dados Coletados</h2>
                <p>Coletamos apenas os dados necessários para o funcionamento do serviço:</p>
                <ul class="list-disc pl-6 space-y-1">
                    <li>Nome completo e nome de usuário</li>
                    <li>Endereço de e-mail</li>
                    <li>Dados financeiros inseridos voluntariamente (carteiras, transações, metas)</li>
                    <li>Dados de acesso (IP, navegador, horários de login)</li>
                </ul>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">3. Finalidade do Tratamento</h2>
                <p>Seus dados são utilizados exclusivamente para:</p>
                <ul class="list-disc pl-6 space-y-1">
                    <li>Fornecer e manter o serviço de gestão financeira</li>
                    <li>Autenticação e segurança da conta</li>
                    <li>Comunicações sobre o serviço</li>
                    <li>Melhorias na experiência do usuário</li>
                </ul>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">4. Compartilhamento de Dados</h2>
                <p>Não compartilhamos, vendemos ou alugamos seus dados pessoais a terceiros. Seus dados financeiros são acessíveis apenas por você.</p>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">5. Segurança</h2>
                <p>Implementamos medidas técnicas e organizacionais para proteger seus dados, incluindo criptografia, controle de acesso e monitoramento contínuo.</p>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">6. Seus Direitos (LGPD, GDPR, CCPA e POPIA)</h2>
                <p>Independentemente da legislação aplicável à sua localização, você tem direito a:</p>
                <ul class="list-disc pl-6 space-y-1">
                    <li>Acessar seus dados pessoais e obter uma cópia deles</li>
                    <li>Corrigir dados incompletos ou desatualizados</li>
                    <li>Solicitar a exclusão dos seus dados ("direito ao esquecimento")</li>
                    <li>Revogar o consentimento a qualquer momento</li>
                    <li>Solicitar a portabilidade dos dados</li>
                    <li>Opor-se ao tratamento e recusar a venda de dados pessoais (CCPA/CPRA)</li>
                    <li>Apresentar reclamação à autoridade de proteção de dados competente</li>
                </ul>
                <p>Para exercer qualquer um desses direitos, incluindo o gerenciamento de cookies, utilize a página de <a href="{{ route('contact') }}" class="text-primary-600 hover:underline">Contato</a> ou as opções disponíveis nas configurações da sua conta.</p>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">7. Retenção de Dados</h2>
                <p>Seus dados são mantidos enquanto sua conta estiver ativa. Após a exclusão da conta, os dados são removidos em até 30 dias, exceto quando houver obrigação legal de retenção.</p>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">8. Contato</h2>
                <p>Para exercer seus direitos ou esclarecer dúvidas sobre esta política, entre em contato através da página de <a href="{{ route('contact') }}" class="text-primary-600 hover:underline">Contato</a>.</p>
            </div>
        </div>
    </div>
</section>
@endsection
