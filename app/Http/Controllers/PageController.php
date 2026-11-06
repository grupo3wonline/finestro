<?php

namespace App\Http\Controllers;

use App\Interfaces\Services\ConsentServiceInterface;
use App\Interfaces\Services\FaqServiceInterface;
use App\Interfaces\Services\PlanPricingInterface;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        protected FaqServiceInterface $faqService
    ) {}

    /**
     * Unified public sales page. Home, Sobre, Serviços, Ferramentas and
     * Contato are all rendered as anchored sections of this single page.
     */
    public function home(): View
    {
        return view('landing.home');
    }

    /**
     * Lightweight contact page (WhatsApp + e-mail, no form). The legacy
     * /sobre, /servicos and /ferramentas URLs are 301 route redirects.
     */
    public function contact(): View
    {
        return view('pages.contact');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function terms(): View
    {
        return view('pages.terms');
    }

    /**
     * Cookie policy: every cookie the app sets, plus the CCPA/CPRA
     * "Não venda ou compartilhe" opt-out.
     */
    public function cookies(Request $request, ConsentServiceInterface $consent): View
    {
        return view('pages.cookies', [
            'currentConsent' => $consent->fromRequest($request),
            'gpc' => $consent->gpcRequested($request),
        ]);
    }

    /**
     * Central de Ajuda (FAQ) pública. Agrupa perguntas ativas por categoria e
     * permite busca simples por termo em pergunta/resposta.
     */
    public function faq(Request $request): View
    {
        return view('pages.faq', $this->faqService->search($request->query('q')));
    }

    /**
     * Dynamic robots.txt driven by the configured app URL so it stays
     * correct across environments (dev, homolog, production).
     */
    public function robots(): Response
    {
        // /login and /register stay crawlable so bots can read their noindex.
        $disallowed = [
            '/admin',
            '/api',
            '/dashboard',
            '/wallets',
            '/transactions',
            '/contas',
            '/orcamentos',
            '/goals',
            '/categories',
            '/calculadoras',
            '/simuladores',
            '/relatorios',
            '/assinatura',
            '/configuracoes',
            '/ia',
            '/profile',
            '/aceitar-termos',
            '/convites',
        ];

        $lines = ['User-agent: *', 'Allow: /'];

        foreach ($disallowed as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.url('/sitemap.xml');

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Dynamic sitemap.xml with exactly the indexable public pages
     * (config/seo.php), so URLs stay in sync with the current domain.
     */
    public function sitemap(): Response
    {
        $today = now()->format('Y-m-d');
        $legalUpdatedAt = $this->legalUpdatedAt() ?? $today;

        $pages = [
            ['route' => 'home', 'changefreq' => 'weekly', 'priority' => '1.0', 'lastmod' => $today],
            ['route' => 'contact', 'changefreq' => 'monthly', 'priority' => '0.8', 'lastmod' => $today],
            ['route' => 'faq', 'changefreq' => 'monthly', 'priority' => '0.7', 'lastmod' => $today],
            ['route' => 'privacy', 'changefreq' => 'yearly', 'priority' => '0.5', 'lastmod' => $legalUpdatedAt],
            ['route' => 'terms', 'changefreq' => 'yearly', 'priority' => '0.5', 'lastmod' => $legalUpdatedAt],
            ['route' => 'cookies', 'changefreq' => 'yearly', 'priority' => '0.5', 'lastmod' => $legalUpdatedAt],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($pages as $page) {
            $xml .= "    <url>\n";
            $xml .= '        <loc>'.e(route($page['route']))."</loc>\n";
            $xml .= '        <lastmod>'.$page['lastmod']."</lastmod>\n";
            $xml .= '        <changefreq>'.$page['changefreq']."</changefreq>\n";
            $xml .= '        <priority>'.$page['priority']."</priority>\n";
            $xml .= "    </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * /llms.txt (llmstxt.org): plain-markdown summary for AI answer engines.
     * A route, not a static file, so the plan price comes from PlanPricing.
     */
    public function llms(PlanPricingInterface $pricing): Response
    {
        $whatsappDisplay = (string) config('marketing.contact.whatsapp_display');
        $whatsappUrl = 'https://wa.me/'.config('marketing.contact.whatsapp_number');
        $email = (string) config('marketing.contact.email');
        $agencyUrl = (string) config('marketing.cta.url');
        // Same source enforcement and the landing pricing table use.
        $free = User::freePlanLimits();

        $lines = [
            '# Finestro',
            '',
            '> Finestro é um sistema web (micro SaaS) de gestão financeira pessoal em português do Brasil. Cada usuário acessa somente os próprios dados: carteiras, receitas e despesas, metas, orçamentos, calculadoras, simuladores, relatórios e insights com IA.',
            '',
            'Público: adultos de 20 a 50 anos, como freelancers, pequenos empreendedores e profissionais que querem controlar as finanças pessoais. Funciona no navegador do computador, tablet e celular, sem instalar aplicativo.',
            '',
            '## Planos',
            '',
            '- Básico: grátis, sem cartão de crédito e sem prazo para acabar. Inclui '.$free['wallets'].' carteira, '.$free['transactions_per_month'].' transações por mês, '.$free['goals'].' metas, '.$free['categories'].' categorias, '.$free['budgets_per_month'].' orçamentos por mês, painel, 7 calculadoras financeiras, testes de perfil e cotação de moedas.',
            '- '.$pricing->planName().': '.$pricing->formattedCurrent().'/mês. Tudo ilimitado, mais compartilhamento de carteiras, simuladores financeiros, relatórios com gráficos e exportação CSV/PDF, recursos de IA, acesso à API REST e suporte prioritário. Cancelamento a qualquer momento, sem multa.',
            '',
            '## Funcionalidades',
            '',
            '- Carteiras (contas bancárias, dinheiro, cartão de crédito com fatura)',
            '- Transações de receita e despesa, recorrentes e parceladas, com comprovantes',
            '- Contas a pagar e a receber',
            '- Metas financeiras com acompanhamento de progresso',
            '- Orçamentos mensais por categoria',
            '- Calculadoras: investimento, juros, aposentadoria, 13º salário, férias, financiamento e cotação de moedas',
            '- Testes de perfil financeiro e de investidor',
            '- Simuladores de investimento, aposentadoria e orçamento (plano '.$pricing->planName().')',
            '- Relatórios com gráficos e exportação (plano '.$pricing->planName().')',
            '- Privacidade: exportação dos dados pessoais e exclusão da conta (LGPD, GDPR, CCPA/CPRA, POPIA)',
            '',
            '## Páginas',
            '',
            '- [Página inicial]('.route('home').'): benefícios, funcionalidades, como usar, dicas, planos e perguntas frequentes',
            '- [Central de Ajuda]('.route('faq').'): perguntas e respostas com busca (?q=termo)',
            '- [Contato]('.route('contact').'): WhatsApp e e-mail',
            '- [Política de Privacidade]('.route('privacy').')',
            '- [Termos de Uso]('.route('terms').')',
            '- [Política de Cookies]('.route('cookies').')',
            '- [Criar conta grátis]('.route('register').')',
            '',
            '## Contato',
            '',
            '- WhatsApp: ['.$whatsappDisplay.']('.$whatsappUrl.')',
            '- E-mail: ['.$email.'](mailto:'.$email.')',
            '',
            '## Desenvolvedor',
            '',
            '- [Grupo 3W Online]('.$agencyUrl.'): empresa que desenvolve sites e sistemas personalizados sob demanda.',
            '- Sistema desenvolvido pelo desenvolvedor HOTWYL | WILLFROMBRASIL.',
        ];

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * Legal pages' last update (Y-m-d) from config/legal.php, or null when unparsable.
     */
    private function legalUpdatedAt(): ?string
    {
        try {
            return Carbon::parse((string) config('legal.policy_updated_at'))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
