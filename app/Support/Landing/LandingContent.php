<?php

declare(strict_types=1);

namespace App\Support\Landing;

/**
 * Static pt-BR content of the public landing page (how-to steps, tips and
 * FAQ). Kept in PHP so the visible markup and structured data share one
 * source. Never touches the database: "/" must render without one.
 */
final class LandingContent
{
    /**
     * Answer-first FAQ shown in the landing #faq section.
     *
     * @return list<array{question: string, answer: string}>
     */
    public static function faq(): array
    {
        return [
            [
                'question' => 'O que é o Finestro?',
                'answer' => 'O Finestro é um sistema web de gestão financeira pessoal. Nele você organiza carteiras, registra receitas e despesas, acompanha metas e orçamentos e usa calculadoras financeiras em um só painel.',
            ],
            [
                'question' => 'O Finestro é gratuito?',
                'answer' => 'Sim. O plano Básico é gratuito, não pede cartão de crédito e não tem prazo para acabar. Quando precisar de mais recursos, você pode assinar o plano Profissional.',
            ],
            [
                'question' => 'Qual a diferença entre o plano Básico e o Profissional?',
                'answer' => 'O Básico inclui 1 carteira, 100 transações por mês, 2 metas, 5 categorias, 3 orçamentos por mês, dashboard, calculadoras, testes de perfil e cotação de moedas. O Profissional libera tudo sem limite e adiciona simuladores, relatórios exportáveis, inteligência artificial, compartilhamento de carteiras e acesso à API.',
            ],
            [
                'question' => 'Meus dados estão seguros?',
                'answer' => 'Sim. Cada conta acessa somente os próprios registros, a conexão é protegida por HTTPS, as senhas são guardadas com hash e o tratamento de dados segue a LGPD. Os detalhes estão na Política de Privacidade.',
            ],
            [
                'question' => 'Posso cancelar a assinatura quando quiser?',
                'answer' => 'Sim. Você cancela o plano Profissional a qualquer momento na área de Assinatura, sem multa. O acesso aos recursos do Profissional continua até o fim do período já pago; depois a conta volta para o plano Básico.',
            ],
            [
                'question' => 'Como exporto ou excluo meus dados?',
                'answer' => 'No plano Profissional você exporta relatórios em CSV e PDF. Para excluir a conta, acesse Configurações: o acesso é encerrado na hora e a assinatura é cancelada. Para pedir uma cópia dos seus dados ou a eliminação definitiva, escreva para contato@3wonline.com.br.',
            ],
            [
                'question' => 'Funciona no celular?',
                'answer' => 'Sim. O Finestro roda no navegador do celular, do tablet e do computador, com layout adaptado a cada tela. Não é preciso instalar nenhum aplicativo.',
            ],
            [
                'question' => 'Quem desenvolve o Finestro?',
                'answer' => 'O Finestro é desenvolvido pelo Grupo 3W Online, empresa que cria sites e sistemas personalizados sob demanda. O sistema foi desenvolvido pelo desenvolvedor HOTWYL | WILLFROMBRASIL.',
            ],
        ];
    }

    /**
     * "Como usar" steps.
     *
     * @return list<array{title: string, text: string, icon: string}>
     */
    public static function steps(): array
    {
        return [
            [
                'title' => 'Cadastre-se',
                'text' => 'Crie sua conta gratuita em menos de 2 minutos. Sem cartão de crédito, sem burocracia.',
                'icon' => 'fa-user-plus',
            ],
            [
                'title' => 'Organize',
                'text' => 'Configure suas carteiras, registre transações e defina metas. Use calculadoras e simuladores gratuitamente.',
                'icon' => 'fa-folder-tree',
            ],
            [
                'title' => 'Prospere',
                'text' => 'Acompanhe relatórios, use IA para insights e tome decisões financeiras mais inteligentes.',
                'icon' => 'fa-chart-line',
            ],
        ];
    }

    /**
     * Practical personal finance tips.
     *
     * @return list<array{title: string, text: string, icon: string}>
     */
    public static function tips(): array
    {
        return [
            [
                'title' => 'Regra 50-30-20',
                'text' => 'Destine 50% para necessidades, 30% para desejos e 20% para poupança.',
                'icon' => 'fa-piggy-bank',
            ],
            [
                'title' => 'Registre Tudo',
                'text' => 'Anote cada gasto, por menor que seja. Pequenos valores somam grandes montantes.',
                'icon' => 'fa-calendar-check',
            ],
            [
                'title' => 'Defina Metas',
                'text' => 'Dê nome, valor e prazo a cada objetivo. Uma meta concreta mostra quanto guardar por mês.',
                'icon' => 'fa-crosshairs',
            ],
            [
                'title' => 'Reserva de Emergência',
                'text' => 'Mantenha de 3 a 6 meses de despesas guardados para imprevistos.',
                'icon' => 'fa-shield-alt',
            ],
            [
                'title' => 'Pague-se Primeiro',
                'text' => 'Assim que a renda cair, separe o valor da poupança antes de pagar as outras contas.',
                'icon' => 'fa-hand-holding-dollar',
            ],
            [
                'title' => 'Revise Assinaturas',
                'text' => 'Uma vez por mês, confira serviços recorrentes e cancele o que você não usa mais.',
                'icon' => 'fa-rotate',
            ],
        ];
    }
}
