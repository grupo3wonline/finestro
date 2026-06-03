@extends('layouts.landing')

@section('title', 'Termos de Uso')
@section('meta_description', 'Termos de Uso do Finestro - Leia nossos termos e condições de uso da plataforma.')

@section('content')
<section class="py-16 bg-gray-50 dark:bg-gray-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8 text-sm" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
                <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
                <li class="text-gray-700 dark:text-gray-300 font-medium">Termos de Uso</li>
            </ol>
        </nav>

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-8 md:p-12 shadow-sm">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Termos de Uso</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">Última atualização: {{ date('d/m/Y') }}</p>

            <div class="prose dark:prose-invert max-w-none space-y-6 text-gray-600 dark:text-gray-400">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">1. Aceitação dos Termos</h2>
                <p>Ao acessar e utilizar o Finestro, você concorda com estes Termos de Uso. Se não concordar com algum dos termos, não utilize a plataforma.</p>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">2. Descrição do Serviço</h2>
                <p>O Finestro é uma plataforma de gestão financeira pessoal que permite aos usuários controlar carteiras, transações, metas e orçamentos. O serviço é oferecido em modelo freemium.</p>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">3. Cadastro e Conta</h2>
                <p>Para utilizar o Finestro, é necessário criar uma conta com informações verdadeiras. Você é responsável por manter a confidencialidade de suas credenciais de acesso.</p>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">4. Uso Adequado</h2>
                <p>O usuário se compromete a utilizar a plataforma de forma ética e legal, não realizando atividades que possam prejudicar o funcionamento do sistema ou outros usuários.</p>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">5. Privacidade e Dados</h2>
                <p>O tratamento de dados pessoais é regido pela nossa <a href="{{ route('privacy') }}" class="text-primary-600 hover:underline">Política de Privacidade</a>, em conformidade com a LGPD (Lei Geral de Proteção de Dados).</p>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">6. Limitação de Responsabilidade</h2>
                <p>O Finestro é uma ferramenta de organização financeira e não constitui aconselhamento financeiro profissional. As decisões financeiras são de responsabilidade exclusiva do usuário.</p>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">7. Modificações</h2>
                <p>Reservamo-nos o direito de modificar estes termos a qualquer momento. Alterações significativas serão comunicadas por e-mail ou notificação na plataforma.</p>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">8. Contato</h2>
                <p>Para dúvidas sobre estes termos, entre em contato através da nossa <a href="{{ route('contact') }}" class="text-primary-600 hover:underline">página de contato</a>.</p>
            </div>
        </div>
    </div>
</section>

@include('components.cta.main')
@endsection
