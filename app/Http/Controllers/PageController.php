<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Unified public sales page. Home, Sobre, Serviços, Ferramentas and
     * Contato are all rendered as anchored sections of this single page.
     */
    public function home(): View
    {
        return view('landing.home');
    }

    /**
     * Legacy public routes now point to the corresponding section of the
     * unified sales page, preserving old links and SEO with 301 redirects.
     */
    public function about(): RedirectResponse
    {
        return redirect()->to(route('home') . '#sobre', 301);
    }

    public function services(): RedirectResponse
    {
        return redirect()->to(route('home') . '#servicos', 301);
    }

    public function tools(): RedirectResponse
    {
        return redirect()->to(route('home') . '#ferramentas', 301);
    }

    public function contact(): RedirectResponse
    {
        return redirect()->to(route('home') . '#contato', 301);
    }

    /**
     * Handle a public contact form submission. Validation, honeypot and
     * throttling are enforced by the request/route; here we only deliver
     * the message and return a flash result the UI turns into a SweetAlert2.
     */
    public function sendContact(StoreContactRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            Mail::raw(
                "Nome: {$data['name']}\n"
                . "E-mail: {$data['email']}\n"
                . "Assunto: {$data['subject']}\n\n"
                . "Mensagem:\n{$data['message']}",
                function ($message) use ($data): void {
                    $message->to(config('mail.contact_to'))
                        ->replyTo($data['email'], $data['name'])
                        ->subject('[Contato Finestro] ' . $data['subject']);
                }
            );
        } catch (\Throwable $e) {
            Log::error('Contact form delivery failed', ['error' => $e->getMessage()]);

            return redirect()->to(route('home') . '#contato')
                ->with('error', 'Não foi possível enviar sua mensagem agora. Tente novamente em instantes ou escreva para contato@3wonline.com.br.');
        }

        return redirect()->to(route('home') . '#contato')
            ->with('success', 'Mensagem enviada com sucesso! Retornaremos em até 24h úteis.');
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
     * Dynamic robots.txt driven by the configured app URL so it stays
     * correct across environments (dev, homolog, production).
     */
    public function robots(): Response
    {
        $disallowed = [
            '/dashboard',
            '/wallets',
            '/transactions',
            '/goals',
            '/simuladores',
            '/calculadoras',
            '/relatorios',
            '/assinatura',
            '/configuracoes',
            '/ia',
            '/profile',
        ];

        $lines = ['User-agent: *', 'Allow: /'];

        foreach ($disallowed as $path) {
            $lines[] = 'Disallow: ' . $path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: ' . url('/sitemap.xml');

        return response(implode("\n", $lines) . "\n", 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Dynamic sitemap.xml built from the public named routes so URLs stay
     * in sync with the current domain.
     */
    public function sitemap(): Response
    {
        $pages = [
            ['route' => 'home', 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['route' => 'privacy', 'changefreq' => 'yearly', 'priority' => '0.5'],
            ['route' => 'terms', 'changefreq' => 'yearly', 'priority' => '0.5'],
        ];

        $today = now()->format('Y-m-d');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($pages as $page) {
            $xml .= "    <url>\n";
            $xml .= '        <loc>' . e(route($page['route'])) . "</loc>\n";
            $xml .= '        <lastmod>' . $today . "</lastmod>\n";
            $xml .= '        <changefreq>' . $page['changefreq'] . "</changefreq>\n";
            $xml .= '        <priority>' . $page['priority'] . "</priority>\n";
            $xml .= "    </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
