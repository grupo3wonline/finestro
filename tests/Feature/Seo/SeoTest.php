<?php

use App\Interfaces\Services\PlanPricingInterface;
use App\Models\FaqItem;
use App\Models\User;
use Illuminate\Testing\TestResponse;

/*
 * SEO / GEO / AEO: per-page meta, robots by route, X-Robots-Tag on admin/API,
 * valid JSON-LD built from PHP arrays, sitemap/robots/llms.txt and the
 * social share images.
 */

const INDEXABLE_ROUTES = ['home', 'contact', 'faq', 'privacy', 'terms', 'cookies'];

/** First match of a regex group in the response HTML. */
function seoMatch(TestResponse $response, string $pattern): ?string
{
    return preg_match($pattern, (string) $response->getContent(), $m) === 1 ? html_entity_decode($m[1]) : null;
}

/**
 * Every JSON-LD block, decoded (throws on invalid JSON).
 *
 * @return list<array<string, mixed>>
 */
function jsonLdBlocks(TestResponse $response): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', (string) $response->getContent(), $matches);

    return array_map(
        static fn (string $json): array => json_decode($json, true, 512, JSON_THROW_ON_ERROR),
        $matches[1],
    );
}

/** @return list<string> */
function jsonLdTypes(TestResponse $response): array
{
    return array_map(static fn (array $block): string => (string) $block['@type'], jsonLdBlocks($response));
}

it('gives every indexable page a distinct title and meta description', function (): void {
    $titles = [];
    $descriptions = [];

    foreach (INDEXABLE_ROUTES as $route) {
        $response = $this->get(route($route))->assertOk();
        $titles[$route] = seoMatch($response, '#<title>(.*?)</title>#s');
        $descriptions[$route] = seoMatch($response, '#<meta name="description" content="([^"]*)">#');

        expect($titles[$route])->not->toBeEmpty()
            ->and($descriptions[$route])->not->toBeEmpty();
    }

    expect(array_unique($titles))->toHaveCount(count(INDEXABLE_ROUTES))
        ->and(array_unique($descriptions))->toHaveCount(count(INDEXABLE_ROUTES));
});

it('sets canonical to the route URL and index robots on indexable pages', function (string $route): void {
    $response = $this->get(route($route, ['utm_source' => 'teste']))->assertOk();

    expect(seoMatch($response, '#<link rel="canonical" href="([^"]*)">#'))->toBe(route($route))
        ->and(seoMatch($response, '#<meta name="robots" content="([^"]*)">#'))->toStartWith('index, follow');
})->with(INDEXABLE_ROUTES);

it('marks auth, app and error pages noindex, nofollow', function (): void {
    foreach (['login', 'register', 'password.request'] as $route) {
        $response = $this->get(route($route))->assertOk();
        expect(seoMatch($response, '#<meta name="robots" content="([^"]*)">#'))->toBe('noindex, nofollow');
    }

    $missing = $this->get('/pagina-que-nao-existe')->assertNotFound();
    expect(seoMatch($missing, '#<meta name="robots" content="([^"]*)">#'))->toBe('noindex, nofollow');

    $dashboard = $this->actingAs(freeUser())->get(route('dashboard'))->assertOk();
    expect(seoMatch($dashboard, '#<meta name="robots" content="([^"]*)">#'))->toBe('noindex, nofollow');
});

it('sends X-Robots-Tag on the admin panel and the API docs', function (): void {
    $this->get('/admin/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->get('/api/documentation')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->getJson('/api/v1/transactions')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->get(route('home'))->assertHeaderMissing('X-Robots-Tag');
});

it('emits valid Organization, WebSite, SoftwareApplication, FAQPage and HowTo JSON-LD on home', function (): void {
    $response = $this->get(route('home'))->assertOk();
    $blocks = collect(jsonLdBlocks($response))->keyBy('@type');

    expect($blocks->keys()->all())->toContain('Organization', 'WebSite', 'SoftwareApplication', 'FAQPage', 'HowTo')
        ->and((string) $response->getContent())->not->toContain('aggregateRating');

    $prices = collect($blocks['SoftwareApplication']['offers'])->pluck('price')->all();
    expect($prices)->toBe(['0', (string) app(PlanPricingInterface::class)->currentPrice()]);

    expect($blocks['Organization']['parentOrganization']['name'])->toBe('Grupo 3W Online')
        ->and(collect($blocks['Organization']['contactPoint'])->pluck('contactType')->unique()->all())->toBe(['customer support'])
        ->and($blocks['WebSite']['potentialAction']['target']['urlTemplate'])->toBe(route('faq').'?q={search_term_string}')
        ->and($blocks['FAQPage']['mainEntity'])->not->toBeEmpty()
        ->and($blocks['HowTo']['step'])->not->toBeEmpty();
});

it('emits FAQPage from the help items plus BreadcrumbList on /ajuda, escaping markup', function (): void {
    FaqItem::factory()->create([
        'question' => 'Como exporto meus "dados"?',
        'answer' => 'Use a página de privacidade </script><b>agora</b> & pronto.',
    ]);

    $response = $this->get(route('faq'))->assertOk();
    $blocks = collect(jsonLdBlocks($response))->keyBy('@type');

    expect($blocks->keys()->all())->toContain('FAQPage', 'BreadcrumbList')
        ->and($blocks['FAQPage']['mainEntity'][0]['name'])->toBe('Como exporto meus "dados"?')
        ->and($blocks['FAQPage']['mainEntity'][0]['acceptedAnswer']['text'])->toContain('</script><b>agora</b> & pronto.');
});

it('emits valid JSON-LD on contact and on pages with a breadcrumb', function (): void {
    expect(jsonLdTypes($this->get(route('contact'))->assertOk()))->toContain('Organization', 'BreadcrumbList');

    $privacy = collect(jsonLdBlocks($this->get(route('privacy'))->assertOk()))->firstWhere('@type', 'BreadcrumbList');
    expect($privacy['itemListElement'][0])->toMatchArray(['position' => 1, 'name' => 'Início', 'item' => route('home')])
        ->and($privacy['itemListElement'][1])->toMatchArray(['position' => 2, 'name' => 'Política de Privacidade']);

    $app = $this->actingAs(freeUser())->get(route('wallets.index'))->assertOk();
    foreach (jsonLdBlocks($app) as $block) {
        expect($block['@context'])->toBe('https://schema.org');
    }
});

it('lists exactly the indexable pages in the sitemap', function (): void {
    $xml = simplexml_load_string((string) $this->get(route('sitemap'))->assertOk()->getContent());

    $locs = [];
    foreach ($xml->url as $url) {
        $locs[] = (string) $url->loc;
        expect((string) $url->lastmod)->toMatch('/^\d{4}-\d{2}-\d{2}$/');
    }

    $expected = array_map(static fn (string $route): string => route($route), INDEXABLE_ROUTES);
    sort($locs);
    sort($expected);

    expect($locs)->toBe($expected);
});

it('disallows private areas in robots.txt and points to the sitemap', function (): void {
    $robots = (string) $this->get(route('robots'))->assertOk()->getContent();

    foreach (['/admin', '/api', '/dashboard', '/wallets', '/transactions', '/contas', '/orcamentos', '/goals', '/categories',
        '/calculadoras', '/simuladores', '/relatorios', '/assinatura', '/configuracoes', '/ia', '/profile', '/aceitar-termos', '/convites'] as $path) {
        expect($robots)->toContain("Disallow: {$path}\n");
    }

    expect($robots)->toContain('Sitemap: '.url('/sitemap.xml'))
        ->and($robots)->not->toContain('Disallow: /login')
        ->and($robots)->not->toContain('Disallow: /register');
});

it('serves llms.txt as plain text with the current plan price', function (): void {
    $this->get('/llms.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('# Finestro', false)
        ->assertSee(app(PlanPricingInterface::class)->formattedCurrent(), false)
        ->assertSee('Grupo 3W Online', false)
        ->assertSee(route('contact'), false);

    expect(file_exists(public_path('llms.txt')))->toBeFalse();
});

it('builds the llms.txt free-plan limits from User::freePlanLimits()', function (): void {
    $free = User::freePlanLimits();

    $this->get('/llms.txt')
        ->assertOk()
        ->assertSee(
            'Inclui '.$free['wallets'].' carteira, '.$free['transactions_per_month'].' transações por mês, '
            .$free['goals'].' metas, '.$free['categories'].' categorias, '.$free['budgets_per_month'].' orçamentos por mês',
            false,
        );
});

it('ships a 1200x630 share image and the PWA icons, and references them', function (): void {
    $og = getimagesize(public_path('og-image.png'));
    expect([$og[0], $og[1]])->toBe([1200, 630]);

    foreach (['apple-touch-icon.png' => 180, 'icon-192.png' => 192, 'icon-512.png' => 512] as $file => $size) {
        $icon = getimagesize(public_path($file));
        expect([$icon[0], $icon[1]])->toBe([$size, $size]);
    }

    $this->get(route('home'))
        ->assertSee(asset('og-image.png'), false)
        ->assertSee(asset('apple-touch-icon.png'), false);

    $manifest = json_decode((string) file_get_contents(public_path('manifest.json')), true, 512, JSON_THROW_ON_ERROR);
    expect($manifest['theme_color'])->toBe('#039855')
        ->and(collect($manifest['icons'])->pluck('src')->all())->toContain('/icon-192.png', '/icon-512.png');
});

it('ships brand-green favicons that the pages and the admin panel reference', function (): void {
    $svg = (string) file_get_contents(public_path('favicon.svg'));
    expect($svg)->toContain('#039855')->and($svg)->toContain('#054f31')->and($svg)->not->toContain('#2563eb');

    // favicon.ico: ICONDIR type 1 with 16, 32 and 48 px PNG entries.
    $ico = (string) file_get_contents(public_path('favicon.ico'));
    $header = unpack('vreserved/vtype/vcount', substr($ico, 0, 6));
    expect($header)->toBe(['reserved' => 0, 'type' => 1, 'count' => 3]);

    $sizes = [];
    foreach (range(0, 2) as $i) {
        $entry = unpack('Cwidth/Cheight/Ccolors/Creserved/vplanes/vbpp/Vlength/Voffset', substr($ico, 6 + 16 * $i, 16));
        $png = getimagesizefromstring(substr($ico, $entry['offset'], $entry['length']));
        expect($png['mime'])->toBe('image/png')->and([$png[0], $png[1]])->toBe([$entry['width'], $entry['height']]);
        $sizes[] = $entry['width'];
    }
    expect($sizes)->toBe([16, 32, 48]);

    $this->get(route('home'))
        ->assertSee('<link rel="icon" type="image/svg+xml" href="'.asset('favicon.svg').'">', false)
        ->assertSee('<link rel="alternate icon" href="'.asset('favicon.ico').'">', false);

    $this->get('/admin/login')->assertOk()->assertSee(asset('favicon.svg'), false);
});
