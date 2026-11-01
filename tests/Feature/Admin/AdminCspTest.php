<?php

declare(strict_types=1);

use Filament\Facades\Filament;

/*
|--------------------------------------------------------------------------
| CSP covers every external origin the panel login loads
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

/**
 * @return array<string, list<string>> directive => sources
 */
function parseCsp(string $header): array
{
    $directives = [];

    foreach (array_filter(array_map('trim', explode(';', $header))) as $directive) {
        $parts = preg_split('/\s+/', $directive) ?: [];
        $name = (string) array_shift($parts);
        $directives[$name] = $parts;
    }

    return $directives;
}

/**
 * @param  array<string, list<string>>  $csp
 */
function cspAllows(array $csp, string $directive, string $origin): bool
{
    $sources = $csp[$directive] ?? $csp['default-src'] ?? [];

    return in_array('https:', $sources, true) || in_array($origin, $sources, true);
}

it('sends a CSP that allows every external origin used by /admin/login', function (): void {
    $response = $this->get('/admin/login')->assertOk();

    $header = (string) $response->headers->get('Content-Security-Policy');
    expect($header)->not->toBe('');

    $csp = parseCsp($header);
    $html = (string) $response->getContent();

    preg_match_all('/<(script|link|img|iframe)\b[^>]*>/i', $html, $tags, PREG_SET_ORDER);

    $checked = [];

    foreach ($tags as [$tag, $name]) {
        if (! preg_match('/\s(?:src|href)=["\'](https:\/\/[^\/"\']+)/i', $tag, $url)) {
            continue;
        }

        $origin = $url[1];
        $name = strtolower($name);

        // Resource hints (preconnect, dns-prefetch) are not fetches governed by CSP.
        if ($name === 'link' && preg_match('/rel=["\'](preconnect|dns-prefetch)["\']/i', $tag)) {
            continue;
        }

        $directive = match ($name) {
            'script' => 'script-src',
            'img' => 'img-src',
            'iframe' => 'frame-src',
            default => 'style-src',
        };

        expect(cspAllows($csp, $directive, $origin))->toBeTrue("{$directive} does not allow {$origin}");
        $checked[] = $origin;
    }

    // The Google font stylesheet is loaded, and its font files come from gstatic.
    expect($checked)->toContain('https://fonts.googleapis.com')
        ->and(cspAllows($csp, 'font-src', 'https://fonts.gstatic.com'))->toBeTrue()
        ->and($html)->not->toContain('fonts.bunny.net');
});
