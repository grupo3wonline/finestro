<?php

use Illuminate\Testing\TestResponse;

/*
|--------------------------------------------------------------------------
| Shared page shell
|--------------------------------------------------------------------------
|
| Every non-admin page renders a header (role="banner"), the exact
| developer footer text and exactly one Grupo 3W Online CTA whose link
| opens www.3wonline.com.br in a new tab.
|
*/

const SHELL_FOOTER_TEXT = 'Sistema desenvolvido pelo desenvolvedor HOTWYL | WILLFROMBRASIL';

function assertPageShell(TestResponse $response): void
{
    $response->assertOk();
    $response->assertSee('role="banner"', false);
    $response->assertSee(SHELL_FOOTER_TEXT);

    $html = (string) $response->getContent();

    expect(substr_count($html, 'data-cta="3wonline"'))->toBe(1)
        ->and($html)->toMatch('/<a\s[^>]*href="https:\/\/www\.3wonline\.com\.br[^"]*"[^>]*target="_blank"[^>]*rel="noopener"/');
}

it('renders the shell on guest pages', function (string $routeName): void {
    assertPageShell($this->get(route($routeName)));
})->with([
    'home',
    'contact',
    'faq',
    'privacy',
    'terms',
    'login',
    'register',
    'password.request',
]);

it('renders the shell on authenticated pages', function (string $routeName): void {
    assertPageShell($this->actingAs(freeUser())->get(route($routeName)));
})->with([
    'dashboard',
    'settings.index',
]);

it('keeps the landing free of fabricated numbers and iframes', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('+500')
        ->assertDontSee('<iframe', false);
});

it('renders landing how-to, tips and FAQ sections with a single h1', function (): void {
    $html = (string) $this->get(route('home'))->assertOk()->getContent();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('id="como-usar"')
        ->and($html)->toContain('id="dicas"')
        ->and($html)->toContain('id="faq"')
        ->and($html)->toContain('id="contato"');
});

it('hides the agency capacity line when no real monthly slots are configured', function (): void {
    config(['marketing.cta.monthly_slots' => null]);

    $html = (string) $this->get(route('home'))->assertOk()->getContent();

    expect($html)->not->toContain('novos projetos por mês')
        ->and($html)->toContain('Diagnóstico gratuito')
        ->and($html)->toContain('Resposta em até 24h úteis')
        ->and($html)->toContain('grid grid-cols-1 gap-3 mx-auto mb-8 text-sm sm:grid-cols-2 max-w-xl');
});

it('shows the agency capacity line with the configured number of slots', function (): void {
    config(['marketing.cta.monthly_slots' => 3]);

    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('Atendemos no máximo 3 novos projetos por mês')
        ->assertSee('sm:grid-cols-3 max-w-3xl', false);
});

it('accepts only a positive integer for MARKETING_CTA_MONTHLY_SLOTS', function (?string $value, ?int $expected): void {
    $key = 'MARKETING_CTA_MONTHLY_SLOTS';
    $restore = static function () use ($key): void {
        unset($_ENV[$key], $_SERVER[$key]);
        putenv($key);
    };

    $restore();

    if ($value !== null) {
        $_ENV[$key] = $_SERVER[$key] = $value;
        putenv($key.'='.$value);
    }

    try {
        $config = require config_path('marketing.php');
        expect($config['cta']['monthly_slots'])->toBe($expected);
    } finally {
        $restore();
    }
})->with([
    'unset' => [null, null],
    'empty' => ['', null],
    'zero' => ['0', null],
    'negative' => ['-2', null],
    'text' => ['quatro', null],
    'positive' => ['6', 6],
]);

it('forwards whitelisted UTM parameters to the sign-up link', function (): void {
    $this->get(route('home', ['utm_source' => 'google', 'utm_medium' => '<script>', 'foo' => 'bar']))
        ->assertOk()
        ->assertSee(route('register', ['utm_source' => 'google']), false)
        ->assertDontSee('foo=bar', false);
});
