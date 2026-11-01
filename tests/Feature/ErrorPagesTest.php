<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;

/*
|--------------------------------------------------------------------------
| Branded error pages
|--------------------------------------------------------------------------
|
| Each HTTP error renders a pt-BR page with the shared shell (footer and
| Grupo 3W Online CTA) and never leaks exception details.
|
*/

beforeEach(function (): void {
    Route::middleware('web')->get('/__err/{code}', fn (int $code) => abort($code));
});

dataset('error pages', [
    '401' => [401, 'Acesso não autorizado'],
    '403' => [403, 'Acesso negado'],
    '404' => [404, 'Página não encontrada'],
    '419' => [419, 'Página expirada'],
    '429' => [429, 'Muitas tentativas'],
    '500' => [500, 'Erro interno do servidor'],
    '503' => [503, 'Em manutenção'],
]);

function assertBrandedErrorPage(TestResponse $response, int $code, string $heading): void
{
    $response->assertStatus($code)
        ->assertSee($heading)
        ->assertSee('Sistema desenvolvido pelo desenvolvedor HOTWYL | WILLFROMBRASIL')
        ->assertSee('data-cta="3wonline"', false)
        ->assertDontSee('Stack trace')
        ->assertDontSee('Exception');
}

it('renders a branded error page for guests', function (int $code, string $heading): void {
    assertBrandedErrorPage($this->get('/__err/'.$code), $code, $heading);
})->with('error pages');

it('renders a branded error page for signed-in users with a way back to the dashboard', function (int $code, string $heading): void {
    $response = $this->actingAs(freeUser())->get('/__err/'.$code);

    assertBrandedErrorPage($response, $code, $heading);
    $response->assertSee(route('dashboard'), false);
})->with('error pages');
