<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Support\Utm;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Testes unitários para App\Support\Utm::fromRequest:
 * verifica que apenas parâmetros UTM da lista branca são retornados
 * e que parâmetros não autorizados são descartados.
 */
class UtmTest extends TestCase
{
    public function test_known_utm_params_are_returned(): void
    {
        $request = Request::create('/', 'GET', [
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'lancamento-2025',
        ]);

        $utm = Utm::fromRequest($request);

        $this->assertArrayHasKey('utm_source', $utm);
        $this->assertArrayHasKey('utm_medium', $utm);
        $this->assertArrayHasKey('utm_campaign', $utm);
        $this->assertSame('google', $utm['utm_source']);
        $this->assertSame('cpc', $utm['utm_medium']);
    }

    public function test_unknown_params_are_not_returned(): void
    {
        $request = Request::create('/', 'GET', [
            'utm_source' => 'newsletter',
            'hack' => 'javascript:alert(1)',
            'admin' => '1',
        ]);

        $utm = Utm::fromRequest($request);

        $this->assertArrayHasKey('utm_source', $utm);
        $this->assertArrayNotHasKey('hack', $utm);
        $this->assertArrayNotHasKey('admin', $utm);
        $this->assertCount(1, $utm);
    }
}
