@extends('layouts.landing')

@section('title', 'Termos de Uso')
@section('meta_description', 'Termos de Uso do Finestro: regras de uso da plataforma, planos Básico e Profissional, cancelamento, privacidade e responsabilidades.')

@php
    $updatedAt = \Illuminate\Support\Carbon::parse((string) config('legal.policy_updated_at'))->format('d/m/Y');
    $h2 = 'text-xl font-semibold text-surface-900 dark:text-white scroll-mt-24';
    $link = 'text-primary-600 dark:text-primary-400 hover:underline';
@endphp

@section('content')
<section class="py-12 sm:py-16 bg-surface-50 dark:bg-surface-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <x-ui.breadcrumb :items="[['label' => 'Termos de Uso']]" />
        </div>

        <article class="bg-white dark:bg-surface-950 rounded-2xl p-6 sm:p-8 md:p-12 shadow-sm border border-surface-100 dark:border-surface-800">
            <h1 class="text-3xl font-bold text-surface-900 dark:text-white mb-3">Termos de Uso</h1>
            <p class="text-sm text-surface-500 dark:text-surface-400 mb-8">
                Versão {{ config('legal.policy_version') }} · Última atualização: {{ $updatedAt }}
            </p>

            <div class="space-y-6 text-surface-700 dark:text-surface-300 leading-relaxed">
                <h2 id="aceitacao" class="{{ $h2 }}">1. Aceitação dos Termos</h2>
                <p>Ao criar uma conta ou usar o Finestro, você concorda com estes Termos e com a <a href="{{ route('privacy') }}" class="{{ $link }}">Política de Privacidade</a>. Se não concordar, não use a plataforma. Quando a versão destes documentos mudar, pediremos seu aceite novamente.</p>

                <h2 id="servico" class="{{ $h2 }}">2. Descrição do Serviço</h2>
                <p>O Finestro é uma plataforma de gestão financeira pessoal para controlar carteiras, transações, contas a pagar e receber, metas e orçamentos, com calculadoras, simuladores, relatórios e recursos de inteligência artificial.</p>

                <h2 id="planos" class="{{ $h2 }}">3. Planos</h2>
                <ul class="list-disc pl-6 space-y-1">
                    <li><strong>Básico</strong>: gratuito, por tempo indeterminado, com limites de uso (por exemplo, de carteiras, transações por mês, metas, categorias e orçamentos) descritos na página de planos.</li>
                    <li><strong>Profissional</strong>: assinatura mensal paga pelo Stripe, sem esses limites e com recursos extras (relatórios com exportação, simuladores, IA, API e compartilhamento de carteiras). O preço vigente é mostrado na página de planos antes da contratação.</li>
                    <li>Você pode cancelar a assinatura a qualquer momento em Assinatura; o acesso Profissional continua até o fim do período já pago e depois a conta volta ao plano Básico, sem perder seus registros.</li>
                    <li>Se um pagamento não for confirmado, a conta passa a funcionar com os limites do plano Básico até a regularização.</li>
                </ul>

                <h2 id="conta" class="{{ $h2 }}">4. Cadastro e Conta</h2>
                <p>Use informações verdadeiras e mantenha usuário, e-mail e senha em sigilo. Você é responsável pelo que for feito com sua conta. O serviço é destinado a maiores de 18 anos.</p>

                <h2 id="uso" class="{{ $h2 }}">5. Uso Adequado</h2>
                <p>Não é permitido usar a plataforma para atividades ilegais, tentar acessar dados de outros usuários, sobrecarregar ou atacar o sistema, nem automatizar acessos fora da API oficial. Contas que violarem estes Termos podem ser suspensas.</p>

                <h2 id="privacidade" class="{{ $h2 }}">6. Privacidade e Dados</h2>
                <p>
                    Seus dados são tratados conforme a <a href="{{ route('privacy') }}" class="{{ $link }}">Política de Privacidade</a> (LGPD, GDPR, CCPA/CPRA e POPIA)
                    e a <a href="{{ route('cookies') }}" class="{{ $link }}">Política de Cookies</a>. Você pode exportar seus dados ou excluir sua conta a qualquer momento em Configurações &gt; Privacidade.
                </p>

                <h2 id="responsabilidade" class="{{ $h2 }}">7. Limitação de Responsabilidade</h2>
                <p>O Finestro é uma ferramenta de organização financeira. Calculadoras, simuladores e análises de IA são estimativas e não constituem aconselhamento financeiro, contábil ou de investimentos. As decisões financeiras são de responsabilidade do usuário.</p>

                <h2 id="modificacoes" class="{{ $h2 }}">8. Modificações</h2>
                <p>Podemos atualizar estes Termos. Mudanças relevantes recebem uma nova versão, que você precisará aceitar ao entrar na plataforma.</p>

                <h2 id="contato" class="{{ $h2 }}">9. Contato</h2>
                <p>Dúvidas sobre estes Termos: <a href="{{ route('contact') }}" class="{{ $link }}">página de contato</a>. Assuntos de privacidade: <a href="mailto:{{ config('privacy.dpo_email') }}" class="{{ $link }}">{{ config('privacy.dpo_email') }}</a>.</p>
            </div>
        </article>
    </div>
</section>

@include('components.cta.main')
@endsection
