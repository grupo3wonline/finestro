<?php

declare(strict_types=1);

use App\Enums\DataExportStatus;
use App\Interfaces\Services\DataExportServiceInterface;
use App\Models\Category;
use App\Models\DataExport;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/*
|--------------------------------------------------------------------------
| Navigation crawler: every page, link and form for each kind of actor
|--------------------------------------------------------------------------
|
| Breadth-first crawl starting from every parameterless GET route plus the
| actor's own records. Each HTML page must carry the shared header, the
| exact footer credit and the Grupo 3W Online CTA; every internal link must
| answer without 404/500 and every form action must resolve to a route for
| its method (forms are never submitted).
|
| Out of scope (covered elsewhere): the Filament panel at /admin
| (tests/Feature/Admin/*), the REST API (tests/Feature/Api/*), Livewire,
| L5 Swagger, Sanctum and storage endpoints. Links into those areas are
| still requested once and their status checked, but not expanded.
|
| Set CRAWLER_DUMP=1 to print every visited URL with its status.
*/

const CRAWLER_MAX_URLS = 400;

const CRAWLER_FOOTER = 'Sistema desenvolvido pelo desenvolvedor HOTWYL | WILLFROMBRASIL';

/** Route-name prefixes that are never used as seeds nor expanded. */
const CRAWLER_SKIPPED_PREFIXES = ['filament.', 'livewire.', 'l5-swagger.', 'sanctum.', 'storage.', 'ignition.', 'api.'];

/** Path prefixes of the same areas, for URLs that match no named route. */
const CRAWLER_SKIPPED_PATHS = ['admin', 'api', 'livewire', 'storage', 'sanctum', 'docs'];

/**
 * Non-HTML or print-only responses: no header/footer/CTA expected.
 */
const CRAWLER_SHELL_EXEMPT = [
    'reports.pdf', 'reports.print', 'reports.export', 'transactions.receipt',
    'settings.privacy.download', 'robots', 'sitemap', 'llms',
];

/**
 * Parameterless GET routes that are not standalone pages: they need data
 * from a third party (Stripe's ?session_id=) and answer 404 without it.
 */
const CRAWLER_SEED_EXCLUDED = ['subscription.success'];

/** Status codes an internal link may answer with. */
const CRAWLER_LINK_STATUSES = [200, 301, 302, 401, 403];

/**
 * @return list<string>
 */
function crawlerHosts(): array
{
    return array_values(array_unique(array_filter([
        parse_url((string) config('app.url'), PHP_URL_HOST),
        'localhost',
        '127.0.0.1',
    ])));
}

function crawlerIsSkippedName(?string $name): bool
{
    if ($name === null) {
        return false;
    }

    foreach (CRAWLER_SKIPPED_PREFIXES as $prefix) {
        if (str_starts_with($name, $prefix)) {
            return true;
        }
    }

    return false;
}

function crawlerIsSkippedPath(string $path): bool
{
    $first = explode('/', trim($path, '/'))[0];

    return in_array($first, CRAWLER_SKIPPED_PATHS, true);
}

/**
 * Seed URLs: parameterless, unsigned, named GET routes of the app.
 *
 * @return list<string>
 */
function crawlerRouteSeeds(): array
{
    $seeds = [];

    /** @var RoutingRoute $route */
    foreach (app('router')->getRoutes() as $route) {
        $name = $route->getName();

        if ($name === null
            || ! in_array('GET', $route->methods(), true)
            || $route->parameterNames() !== []
            || crawlerIsSkippedName($name)
            || in_array($name, CRAWLER_SEED_EXCLUDED, true)
            || in_array('signed', $route->gatherMiddleware(), true)) {
            continue;
        }

        $seeds[] = route($name);
    }

    return $seeds;
}

/**
 * Own records of the actor, so show/edit/invoice/receipt/download pages are
 * crawled too.
 *
 * @return list<string>
 */
function crawlerRecordSeeds(User $user): array
{
    $card = Wallet::factory()->for($user)->create([
        'type' => 'credit', 'balance' => 0, 'credit_limit' => 3000, 'closing_day' => 5, 'due_day' => 12,
    ]);
    $checking = Wallet::factory()->for($user)->create(['type' => 'checking', 'name' => 'Conta do crawler']);
    $category = Category::factory()->create(['user_id' => $user->id]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $checking->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'status' => 'pending',
        'date' => now()->toDateString(),
        'is_recurring' => false,
    ]);
    $transaction->addMediaFromString("%PDF-1.4\n%%EOF")
        ->usingFileName('comprovante.pdf')
        ->toMediaCollection(Transaction::RECEIPT_COLLECTION);

    $goal = Goal::factory()->create(['user_id' => $user->id]);

    $export = DataExport::factory()->for($user)->create([
        'status' => DataExportStatus::Ready,
        'path' => "exports/{$user->id}/crawler.zip",
        'size' => 3,
        'completed_at' => now(),
        'expires_at' => now()->addHours((int) config('privacy.export_ttl_hours')),
    ]);
    Storage::disk((string) config('privacy.export_disk'))->put((string) $export->path, 'zip');

    return [
        route('wallets.show', $card),
        route('wallets.edit', $card),
        route('wallets.invoice', $card),
        route('wallets.show', $checking),
        route('transactions.show', $transaction),
        route('transactions.edit', $transaction),
        route('goals.show', $goal),
        route('goals.edit', $goal),
        route('categories.edit', $category),
        app(DataExportServiceInterface::class)->downloadUrl($export),
    ];
}

/**
 * Absolute URL for an internal href, or null when it points elsewhere.
 */
function crawlerInternalUrl(string $href, string $currentUrl): ?string
{
    $href = (string) preg_replace('/#.*$/', '', $href);

    if ($href === '') {
        return null;
    }

    if (str_starts_with($href, '//')) {
        $href = 'https:'.$href;
    } elseif (str_starts_with($href, '/')) {
        $href = rtrim((string) config('app.url'), '/').$href;
    } elseif (! preg_match('#^[a-z][a-z0-9+.-]*:#i', $href)) {
        // Relative path: resolve against the current page's directory.
        $base = (string) preg_replace('#[^/]*$#', '', (string) strtok($currentUrl, '?'));
        $href = $base.$href;
    }

    $host = parse_url($href, PHP_URL_HOST);

    return in_array($host, crawlerHosts(), true) ? $href : null;
}

/**
 * Visited-set key: path plus the *names* of the query parameters, so month
 * navigation (?month=4, ?month=3, ...) does not become an endless chain.
 */
function crawlerKey(string $url): string
{
    $path = '/'.trim((string) parse_url($url, PHP_URL_PATH), '/');
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
    $names = array_map('strval', array_keys($query));
    sort($names);

    return $path.($names === [] ? '' : '?'.implode('&', $names));
}

function crawlerMatch(string $url, string $method = 'GET'): ?RoutingRoute
{
    try {
        return app('router')->getRoutes()->match(Request::create($url, $method));
    } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
        return null;
    }
}

function crawlerGet(?User $actor, string $url): TestResponse
{
    $test = test();

    return $actor instanceof User ? $test->actingAs($actor)->get($url) : $test->get($url);
}

/**
 * Checks one external/special href; returns a problem description or null.
 */
function crawlerExternalProblem(DOMElement $anchor, string $href): ?string
{
    if (str_starts_with($href, 'mailto:')) {
        $address = rawurldecode((string) strtok(substr($href, 7), '?'));

        return filter_var($address, FILTER_VALIDATE_EMAIL) ? null : "invalid mailto: {$href}";
    }

    if (str_starts_with($href, 'tel:')) {
        return preg_match('/^tel:\+?[0-9()\s-]{8,}$/', $href) ? null : "invalid tel: {$href}";
    }

    if (preg_match('#^https?://wa\.me/#i', $href)) {
        return preg_match('#^https://wa\.me/5541996924829(\?text=[^\s]+)?$#', $href) ? null : "invalid WhatsApp link: {$href}";
    }

    if (preg_match('#^https?://(www\.)?3wonline\.com\.br#i', $href)) {
        if (! str_starts_with($href, 'https://www.3wonline.com.br')) {
            return "3W link must use https://www.3wonline.com.br: {$href}";
        }

        $rel = strtolower($anchor->getAttribute('rel'));

        return $anchor->getAttribute('target') === '_blank' && str_contains($rel, 'noopener')
            ? null
            : "3W link without target=_blank rel=noopener: {$href}";
    }

    return filter_var($href, FILTER_VALIDATE_URL) && str_starts_with($href, 'https://')
        ? null
        : "external link is not a valid https URL: {$href}";
}

/**
 * Problems in the links, forms and shared shell of one HTML page.
 *
 * @param  list<array{0: string, 1: string, 2: bool}>  $queue  internal links are appended
 * @return list<string>
 */
function crawlerInspectPage(string $html, string $url, array &$queue): array
{
    $problems = [];

    foreach ([
        'role="banner"' => 'header (role="banner")',
        CRAWLER_FOOTER => 'footer credit',
        'data-cta="3wonline"' => 'Grupo 3W Online CTA',
    ] as $needle => $label) {
        if (! str_contains($html, $needle)) {
            $problems[] = "missing {$label} on {$url}";
        }
    }

    if (stripos($html, '<iframe') !== false) {
        $problems[] = "iframe on {$url}";
    }
    if (stripos($html, 'freshdesk') !== false) {
        $problems[] = "freshdesk reference on {$url}";
    }

    $previous = libxml_use_internal_errors(true);
    $dom = new DOMDocument;
    $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    foreach ($dom->getElementsByTagName('a') as $anchor) {
        if (! $anchor instanceof DOMElement || ! $anchor->hasAttribute('href')) {
            continue;
        }

        $href = trim($anchor->getAttribute('href'));

        if ($href === '' || str_starts_with($href, '#') || str_starts_with(strtolower($href), 'javascript:')) {
            continue;
        }

        $internal = crawlerInternalUrl($href, $url);

        if ($internal === null) {
            if (($problem = crawlerExternalProblem($anchor, $href)) !== null) {
                $problems[] = "{$problem} (on {$url})";
            }

            continue;
        }

        $queue[] = [$internal, $url, false];
    }

    foreach ($dom->getElementsByTagName('form') as $form) {
        // Forms without a static action are bound by Alpine (:action) or
        // post to the current URL; only literal actions are checked.
        if (! $form instanceof DOMElement || ! $form->hasAttribute('action')) {
            continue;
        }

        $rawAction = trim($form->getAttribute('action'));
        $action = $rawAction === '' ? $url : crawlerInternalUrl($rawAction, $url);

        if ($action === null) {
            $problems[] = "form posts to an external URL {$rawAction} (on {$url})";

            continue;
        }

        $method = strtoupper($form->getAttribute('method') ?: 'GET');

        if ($method === 'POST') {
            foreach ($form->getElementsByTagName('input') as $input) {
                if ($input instanceof DOMElement && $input->getAttribute('name') === '_method') {
                    $method = strtoupper($input->getAttribute('value'));
                }
            }
        }

        if (! crawlerMatch($action, $method) instanceof RoutingRoute) {
            $problems[] = "form {$method} {$action} matches no route (on {$url})";
        }
    }

    return $problems;
}

/**
 * Crawls the app as $actor and returns the problems found.
 *
 * @param  list<string>  $seeds
 * @return array{problems: list<string>, visited: array<string, int>}
 */
function crawlApp(?User $actor, array $seeds): array
{
    $problems = [];
    $queue = array_map(fn (string $seed): array => [$seed, 'seed', true], $seeds);
    $visited = [];

    while ($queue !== [] && count($visited) < CRAWLER_MAX_URLS) {
        [$url, $from, $isSeed] = array_shift($queue);
        $key = crawlerKey($url);

        if (isset($visited[$key])) {
            continue;
        }

        $routeName = crawlerMatch($url)?->getName();
        $path = (string) parse_url($url, PHP_URL_PATH);
        $response = crawlerGet($actor, $url);
        $status = $response->getStatusCode();
        $visited[$key] = $status;

        // Seeds may legitimately answer 4xx (e.g. a guest-only page for a
        // user); discovered links must be one of CRAWLER_LINK_STATUSES.
        $broken = $isSeed ? ($status === 404 || $status >= 500) : ! in_array($status, CRAWLER_LINK_STATUSES, true);

        if ($broken) {
            $problems[] = "{$status} on {$url} (from {$from})";

            continue;
        }

        if ($response->isRedirection()) {
            $next = crawlerInternalUrl((string) $response->headers->get('Location'), $url);

            if ($next !== null && ! crawlerIsSkippedPath((string) parse_url($next, PHP_URL_PATH))) {
                $queue[] = [$next, $url, false];
            }

            continue;
        }

        if (! str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            || crawlerIsSkippedName($routeName)
            || crawlerIsSkippedPath($path)
            || in_array($routeName, CRAWLER_SHELL_EXEMPT, true)) {
            continue;
        }

        array_push($problems, ...crawlerInspectPage((string) $response->getContent(), $url, $queue));
    }

    $pending = array_filter($queue, fn (array $item): bool => ! isset($visited[crawlerKey($item[0])]));
    if ($pending !== []) {
        $problems[] = 'crawl cap of '.CRAWLER_MAX_URLS.' URLs reached with '.count($pending).' URLs left; raise CRAWLER_MAX_URLS';
    }

    return ['problems' => array_values(array_unique($problems)), 'visited' => $visited];
}

beforeEach(function (): void {
    Http::preventStrayRequests();
    Storage::fake('local');
    Storage::fake('private');
});

it('crawls every page, link and form without broken targets', function (?User $actor): void {
    $seeds = crawlerRouteSeeds();
    if ($actor instanceof User) {
        $seeds = [...$seeds, ...crawlerRecordSeeds($actor)];
    }

    $result = crawlApp($actor, $seeds);

    if (getenv('CRAWLER_DUMP') === '1') {
        fwrite(STDERR, PHP_EOL.($actor?->role ?? 'guest').' visited '.count($result['visited']).PHP_EOL);
        foreach ($result['visited'] as $key => $status) {
            fwrite(STDERR, "  {$status} {$key}".PHP_EOL);
        }
    }

    expect($result['problems'])->toBe([], implode(PHP_EOL, $result['problems']))
        ->and(count($result['visited']))->toBeGreaterThan(20);
})->with([
    'guest' => fn (): ?User => null,
    'Free' => fn (): User => freeUser(),
    'Profissional' => fn (): User => proUser(),
    'admin' => fn (): User => User::factory()->admin()->create(),
]);

it('shows no form on the landing and contact pages for guests', function (string $path): void {
    $this->get($path)->assertOk()->assertDontSee('<form', false);
})->with(['/', '/contato']);

it('keeps the merged legacy pages as permanent redirects', function (string $path, string $anchor): void {
    $this->get($path)->assertStatus(301)->assertRedirect(url('/'.$anchor));
})->with([
    ['/sobre', '#sobre'],
    ['/servicos', '#servicos'],
    ['/ferramentas', '#ferramentas'],
]);
