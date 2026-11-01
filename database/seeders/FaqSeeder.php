<?php

namespace Database\Seeders;

use App\Models\FaqCategory;
use App\Models\FaqItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FaqSeeder extends Seeder
{
    /**
     * Conteúdo da Central de Ajuda em pt-BR. Idempotente: usa updateOrCreate
     * por slug/pergunta para poder rodar novamente sem duplicar.
     */
    public function run(): void
    {
        $data = [
            [
                'name' => 'Primeiros passos',
                'icon' => 'fa-rocket',
                'items' => [
                    ['q' => 'O que é o Finestro?', 'a' => 'O Finestro é um sistema de gestão financeira pessoal que ajuda você a controlar carteiras, receitas, despesas, metas e orçamentos em um só lugar. Cada usuário acessa apenas os seus próprios dados.'],
                    ['q' => 'Como faço para criar minha conta?', 'a' => 'Clique em "Registrar", informe seu nome de usuário, e-mail e senha. Você receberá um e-mail de confirmação; após confirmar, já poderá acessar o painel e começar a usar.'],
                    ['q' => 'O Finestro é gratuito?', 'a' => 'Sim, há um plano gratuito com 1 carteira, até 100 lançamentos por mês, 2 metas e categorias básicas. O plano Profissional libera carteiras, metas e lançamentos ilimitados, simuladores, exportação de relatórios e análises com inteligência artificial.'],
                    ['q' => 'Por onde começo depois de entrar?', 'a' => 'Comece cadastrando uma carteira (sua conta ou cartão) e algumas categorias. Em seguida, registre suas receitas e despesas. O painel passa a mostrar seu resumo, gráficos e alertas automaticamente.'],
                ],
            ],
            [
                'name' => 'Carteiras e transações',
                'icon' => 'fa-wallet',
                'items' => [
                    ['q' => 'O que é uma carteira?', 'a' => 'Uma carteira representa uma conta ou meio de pagamento: conta corrente, poupança, dinheiro em espécie, cartão de crédito ou investimento. Todas as transações são vinculadas a uma carteira.'],
                    ['q' => 'Como funcionam as contas a pagar e a receber?', 'a' => 'Ao lançar uma transação, você pode marcá-la como pendente e definir a data de vencimento. Ela aparece em "Contas" até ser quitada. Quando você marca como paga, o saldo da carteira é atualizado.'],
                    ['q' => 'Posso parcelar uma compra?', 'a' => 'Sim. Ao registrar uma despesa no cartão de crédito, informe o número de parcelas. O sistema divide o valor, mantém os centavos corretos e, no cartão, consome o limite disponível imediatamente.'],
                    ['q' => 'Como registro uma despesa que se repete todo mês?', 'a' => 'Ative a opção de recorrência na transação e escolha a frequência (por exemplo, mensal). O sistema gera automaticamente os próximos lançamentos com antecedência.'],
                    ['q' => 'Excluí uma transação por engano. E agora?', 'a' => 'Transações excluídas vão para a lixeira e podem ser restauradas. Ao restaurar, o saldo da carteira é reajustado para o estado correto.'],
                ],
            ],
            [
                'name' => 'Cartão de crédito e faturas',
                'icon' => 'fa-credit-card',
                'items' => [
                    ['q' => 'Como o Finestro calcula a fatura do cartão?', 'a' => 'A fatura respeita o ciclo real de fechamento e vencimento que você configura na carteira do cartão, e não apenas o mês do calendário. Assim os lançamentos caem na fatura certa.'],
                    ['q' => 'Como pago a fatura do cartão?', 'a' => 'Na tela da fatura, use a opção de pagamento e escolha a carteira de origem (por exemplo, sua conta corrente). O valor é transferido de uma carteira para a outra em uma única operação.'],
                ],
            ],
            [
                'name' => 'Metas e orçamentos',
                'icon' => 'fa-bullseye',
                'items' => [
                    ['q' => 'Como funcionam as metas financeiras?', 'a' => 'Você define um valor-alvo e um prazo. O Finestro acompanha o progresso e mostra o quanto falta para atingir cada meta.'],
                    ['q' => 'O que é um orçamento mensal?', 'a' => 'É um teto de gastos para o mês, geral ou por categoria. O sistema compara seus gastos com o teto e alerta quando você se aproxima do limite definido.'],
                ],
            ],
            [
                'name' => 'Relatórios e exportação',
                'icon' => 'fa-chart-pie',
                'items' => [
                    ['q' => 'Consigo exportar meus dados?', 'a' => 'No plano Profissional você exporta relatórios em CSV e em PDF, além de poder receber o resumo do período por e-mail com o CSV anexado.'],
                    ['q' => 'Qual a diferença entre "PDF" e "Imprimir"?', 'a' => 'O botão PDF gera o arquivo direto pelo sistema, pronto para baixar. O botão Imprimir abre uma versão para impressão que também permite salvar como PDF pelo próprio navegador.'],
                ],
            ],
            [
                'name' => 'Segurança e privacidade',
                'icon' => 'fa-shield-halved',
                'items' => [
                    ['q' => 'Meus dados financeiros estão seguros?', 'a' => 'Sim. Cada usuário acessa somente os próprios registros, as senhas são armazenadas de forma cifrada e chaves sensíveis, como as de integração com IA, são guardadas criptografadas.'],
                    ['q' => 'O Finestro está em conformidade com a LGPD?', 'a' => 'Sim. Tratamos seus dados conforme a LGPD, com política de privacidade, consentimento e a possibilidade de excluir sua conta e seus dados a qualquer momento nas configurações.'],
                    ['q' => 'Como excluo minha conta?', 'a' => 'Acesse Configurações e use a opção de exclusão de conta. Após a confirmação, seus dados são removidos conforme descrito na política de privacidade.'],
                ],
            ],
        ];

        foreach ($data as $catIndex => $category) {
            $faqCategory = FaqCategory::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'icon' => $category['icon'],
                    'sort_order' => $catIndex,
                    'is_active' => true,
                ]
            );

            foreach ($category['items'] as $itemIndex => $item) {
                FaqItem::updateOrCreate(
                    ['faq_category_id' => $faqCategory->id, 'question' => $item['q']],
                    [
                        'answer' => $item['a'],
                        'sort_order' => $itemIndex,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
