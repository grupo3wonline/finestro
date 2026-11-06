<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use RectorLaravel\Rector\ClassMethod\ScopeNamedClassMethodToScopeAttributedClassMethodRector;
use RectorLaravel\Set\LaravelSetList;

/*
 * Rector configuration for Finestro.
 *
 * Run `php vendor/bin/rector --dry-run` to preview and `php vendor/bin/rector` to apply.
 * Rector runs before Pint, so Pint normalises whatever Rector prints.
 */
return RectorConfig::configure()
    // Only first-party PHP. vendor, storage, bootstrap/cache, resources/views (Blade)
    // and tests are excluded simply by not being listed here.
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
    ])
    ->withSkip([
        // Migrations are historical records already run in production; never rewrite them.
        __DIR__.'/database/migrations',
        // readonly properties/classes break Laravel patterns that assign after construction
        // (container setters, model hydration, test doubles extending the class).
        ReadOnlyPropertyRector::class,
        ReadOnlyClassRector::class,
        // Keep the classic scopeXxx() methods instead of the #[Scope] attribute: the
        // rewrite touches every model for no functional gain.
        ScopeNamedClassMethodToScopeAttributedClassMethodRector::class,
    ])
    // No arguments: the PHP level is derived from composer.json ("php": "^8.3").
    // Staying at the ^8.3 level is a deliberately conservative choice so Rector does not
    // rewrite working code into 8.4/8.5-only syntax. Raising the level is a separate decision.
    ->withPhpSets()
    // Conservative prepared sets: remove dead code, simplify expressions, add types
    // that Rector can prove from the code itself.
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
    )
    // Laravel 12 upgrade rules. Do not use the LARAVEL_130 sets while the app runs on 12:
    // they rewrite code to Laravel 13 APIs.
    ->withSets([
        LaravelSetList::LARAVEL_120,
    ])
    // Import fully qualified class names, but leave global short names (e.g. \Exception)
    // alone, and drop imports that are no longer used.
    ->withImportNames(importShortClasses: false, removeUnusedImports: true);
