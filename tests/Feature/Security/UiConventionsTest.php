<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

/*
|--------------------------------------------------------------------------
| UI and security conventions that must not regress
|--------------------------------------------------------------------------
|
| These tests read the Blade views and front-end sources so a new screen that
| breaks a project rule (autocomplete, native dialogs, off-palette colours,
| unescaped output) fails the build instead of reaching production.
*/

/**
 * @return array<string, string> relative path => contents, without Blade comments
 */
function uiConventionViews(): array
{
    $views = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (str_ends_with($file->getFilename(), '.blade.php')) {
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
            $views[$relative] = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents($file->getPathname()));
        }
    }

    return $views;
}

/**
 * Opening tags of the given elements, aware of quotes and {{ }} expressions.
 *
 * @param  list<string>  $elements
 * @return list<string>
 */
function uiConventionTags(string $html, array $elements): array
{
    $piece = '(?:\{\{.*?\}\}|\{!!.*?!!\}|"[^"]*"|\'[^\']*\'|[^>"\'{])+';
    preg_match_all('/<(?:'.implode('|', $elements).')\b(?:'.$piece.')?>/s', $html, $matches);

    return $matches[0];
}

it('puts autocomplete="off" on every form and every text-like control', function (): void {
    $missing = [];
    $skippedTypes = '/\btype\s*=\s*["\']?(hidden|checkbox|radio|submit|button|file|color|range|image|reset)\b/i';

    foreach (uiConventionViews() as $path => $html) {
        foreach (uiConventionTags($html, ['form', 'input', 'select', 'textarea']) as $tag) {
            if (preg_match('/\bautocomplete\s*=/i', $tag) || preg_match($skippedTypes, $tag)) {
                continue;
            }
            $missing[] = $path.' => '.preg_replace('/\s+/', ' ', mb_substr($tag, 0, 90));
        }
    }

    expect($missing)->toBeEmpty();
});

it('uses SweetAlert2 instead of native dialogs and keeps jQuery out', function (): void {
    $offenders = [];

    foreach (uiConventionViews() as $path => $html) {
        if (preg_match('/(?<![\w.])(alert|confirm)\(|\bonsubmit\s*=|\$\(document\)|jquery/i', $html)) {
            $offenders[] = $path;
        }
    }

    expect($offenders)->toBeEmpty();
});

it('keeps the brand palette: surface-* instead of gray-*', function (): void {
    $offenders = [];

    foreach (uiConventionViews() as $path => $html) {
        if (preg_match('/\b(?:bg|text|border|ring|from|to|via|divide|placeholder|fill|stroke)-gray-\d/', $html)) {
            $offenders[] = $path;
        }
    }

    expect($offenders)->toBeEmpty()
        ->and(file_get_contents(resource_path('css/app.css')))->not->toMatch('/\bgray-\d/');
});

it('only prints unescaped output where it is safe (JSON-LD with hex-escaped tags)', function (): void {
    $withRawOutput = array_keys(array_filter(uiConventionViews(), fn (string $html): bool => str_contains($html, '{!!')));

    expect($withRawOutput)->toBe(['resources/views/components/seo/json-ld.blade.php']);

    expect(file_get_contents(resource_path('views/components/seo/json-ld.blade.php')))
        ->toContain('JSON_HEX_TAG')
        ->toContain('JSON_HEX_AMP');
});

it('shows the app in Portuguese: the navigation says Painel, not Dashboard', function (): void {
    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Painel')
        ->assertDontSee('>Dashboard<', false)
        ->assertDontSee('</i> Dashboard', false);

    $this->get(route('home'))->assertOk()->assertDontSee('Dashboard completo')->assertDontSee('Dashboard com resumo');
});

it('debounces the search boxes and avoids sending the same search twice', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('transactions.index'))->assertOk()
        ->assertSee('type="search"', false)
        ->assertSee('data-debounce-submit="600"', false);

    $this->get(route('faq'))->assertOk()->assertSee('data-debounce-submit="700"', false);

    $js = (string) file_get_contents(resource_path('js/forms.js'));

    expect($js)
        ->toContain('data-submitting')           // second submit while in flight is cancelled
        ->toContain('bindAutoSearch')            // search-as-you-type with debounce
        ->toContain('pageshow');                 // locked forms are released from the bfcache
});

it('limits form writes per signed-in user and never counts reads', function (): void {
    config(['security.write_rate_limit_per_minute' => 3]);
    RateLimiter::clear('app-writes:'.User::factory()->create()->getAuthIdentifier());

    $user = User::factory()->create();
    $other = User::factory()->create();

    foreach (range(1, 3) as $attempt) {
        $this->actingAs($user)->post(route('categories.store'), ['name' => ''])->assertSessionHasErrors('name');
    }

    $this->actingAs($user)->post(route('categories.store'), ['name' => ''])->assertStatus(429);
    $this->actingAs($user)->put(route('settings.password'), [])->assertStatus(429);

    // Reads keep working for the blocked user, and other users are unaffected.
    $this->actingAs($user)->get(route('categories.index'))->assertOk();
    $this->actingAs($other)->post(route('categories.store'), ['name' => ''])->assertSessionHasErrors('name');

    expect(Category::query()->where('user_id', $user->id)->count())->toBe(0);
});

it('does not count GET requests against the write limit', function (): void {
    config(['security.write_rate_limit_per_minute' => 2]);
    $user = User::factory()->create();

    foreach (range(1, 10) as $attempt) {
        $this->actingAs($user)->get(route('categories.index'))->assertOk();
    }

    $this->actingAs($user)->post(route('categories.store'), ['name' => ''])->assertSessionHasErrors('name');
});

it('builds every breadcrumb with the component (accessible markup + BreadcrumbList JSON-LD)', function (): void {
    $handMade = array_keys(array_filter(uiConventionViews(), fn (string $html): bool => str_contains($html, 'aria-label="Breadcrumb"')));

    expect($handMade)->toBeEmpty();

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('calculators.index'))->assertOk()
        ->assertSee('aria-label="Trilha de navegação"', false)
        ->assertSee('"@type":"BreadcrumbList"', false)
        ->assertSee('Calculadoras');
});

it('opens external tabs with rel="noopener noreferrer"', function (): void {
    $offenders = [];

    foreach (uiConventionViews() as $path => $html) {
        foreach (uiConventionTags($html, ['a']) as $tag) {
            if (str_contains($tag, 'target="_blank"') && ! preg_match('/rel="[^"]*noopener[^"]*"/', $tag)) {
                $offenders[] = $path;
            }
        }
    }

    expect(array_unique($offenders))->toBeEmpty();
});

it('sends the session cookie as Secure, HttpOnly and SameSite by default in production', function (): void {
    $config = (string) file_get_contents(config_path('session.php'));

    expect($config)
        ->toContain("env('SESSION_SECURE_COOKIE', env('APP_ENV') === 'production')")
        ->toContain("'http_only' => env('SESSION_HTTP_ONLY', true)")
        ->toContain("'same_site' => env('SESSION_SAME_SITE', 'lax')");
});
