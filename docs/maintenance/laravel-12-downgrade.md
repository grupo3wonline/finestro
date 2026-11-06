# Laravel 13 → Laravel 12

Date: 2026-11-01. Scope: run the application on Laravel 12 instead of Laravel 13, keeping every other package and all behaviour as they were.

## Result

| | Before | After |
| --- | --- | --- |
| `laravel/framework` | v13.39.0 | **v12.71.0** (`^12.0`) |
| Symfony components (http-foundation, http-kernel, routing, mailer, mime, process, finder, error-handler, uid, var-dumper) | 8.1.x | 7.4.x (what Laravel 12 requires) |
| Other packages | | untouched, except the patch bumps below |

The update was minimal: `composer update laravel/framework --with-dependencies` (20 packages, all framework dependencies). Filament 5.11, Livewire 4, Sanctum 4.3, Scout 11.9, Spatie packages, Excel 4, L5-Swagger 11.2, Pest 4 and PHPUnit 12 all support Laravel 12 and kept their versions. The `php` requirement in `composer.json` stays `^8.3`. **Moving to Laravel 12 does not lower the PHP version the application needs**: the lock still requires PHP 8.4.1 or newer (`composer why-not php 8.3.0`), because of `spatie/laravel-activitylog` 5.x and `spatie/image` (`^8.4`) and the Symfony 8 components that are not tied to Laravel (`translation`, `yaml`, `html-sanitizer`, `string`, `type-info`, `clock`, `css-selector`, `event-dispatcher`). A host that runs PHP 8.2 or 8.3 would also need `spatie/laravel-activitylog` 4.x (and its code changes) and an older `spatie/image`. The lock now drops `symfony/polyfill-php86` and adds `symfony/polyfill-php83`.

Two security patches were applied with the update because new advisories (published 2026-11-04) hit the old lock: `symfony/html-sanitizer` and `symfony/yaml`, both 8.1.10 → 8.1.11. `composer audit --locked` reports no advisories; `npm audit --omit=dev --audit-level=high` is clean. The only audit note is the abandoned `symplify/rule-doc-generator-contracts` (dev-only, see the phase 6.5 document).

## Code changes

Only the class that Laravel 13 renamed:

| File | Change |
| --- | --- |
| `app/Providers/Filament/AdminPanelProvider.php` | `PreventRequestForgery` → `ValidateCsrfToken` (admin panel middleware list) |
| `config/sanctum.php` | `validate_csrf_token` → `ValidateCsrfToken::class` |
| `tests/Feature/Security/HardeningTest.php` | `enforceCsrf()` binds a subclass of `ValidateCsrfToken` |
| `rector.php` | `LARAVEL_130_WITHOUT_ATTRIBUTES` → `LARAVEL_120`; skips `ScopeNamedClassMethodToScopeAttributedClassMethodRector` so the `scopeXxx()` methods stay as they are |

`bootstrap/app.php`, the models, the routes and the other config files needed no change. `$middleware->validateCsrfTokens(except: [...])` works the same in Laravel 12.

## Verification

- `php artisan about`: Laravel 12.71.0, PHP 8.5.6.
- Full suite: 1063 passed, 15 skipped (same numbers as on Laravel 13).
- `composer quality` (Pint, PHPStan level 6 with the same baseline, Rector, tests) exits 0.

## What to do on the server

Deploy is FTP only, so `vendor/` has to be refreshed by hand because the Symfony components changed major version (8 → 7.4):

1. Upload the new `composer.json`, `composer.lock` and `vendor/` (or run `composer install --no-dev --optimize-autoloader` where Composer is available).
2. `php artisan optimize:clear`, then `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
3. `php artisan filament:upgrade` runs from `composer install`; if `--no-scripts` was used, run it manually.
4. No migration is involved in this change.

Do not mix an old `vendor/` (Symfony 8) with the new code, or the old `bootstrap/cache/*.php` files: delete `bootstrap/cache/packages.php` and `services.php` if they are present on the server.

## How to revert

The previous files are in `.agents/tasks/laravel-12-downgrade/backup/` (`composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `rector.php`, `config/sanctum.php`). Copy them back, restore the three `PreventRequestForgery` references, then `composer install` and `php artisan optimize:clear`.
