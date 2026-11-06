# Phase 3: Quality tooling

This document describes the test, style, static analysis and refactoring tools added in Phase 3, how to run them, and the decisions behind their configuration.

## Installed versions

All tools are `require-dev` dependencies.

| Package | Constraint | Installed |
| --- | --- | --- |
| pestphp/pest | ^4.7 | 4.7.8 |
| pestphp/pest-plugin-laravel | ^4.1 | 4.1.0 |
| phpunit/phpunit | ^12.5.33 | 12.5.33 |
| nunomaduro/collision | ^8.9.5 | 8.9.5 |
| larastan/larastan | ^3.13 | 3.13.2 |
| phpstan/phpstan | (via Larastan/Rector) | 2.3.4 |
| rector/rector | ^2.8 | 2.8.3 |
| driftingly/rector-laravel | ^2.7 | 2.7.3 |
| laravel/pint | ^1.27 | 1.29.1 |

Framework: laravel/framework 13.9.0 on PHP 8.5 (composer.json declares `php: ^8.3`).

Notes from installation:

- Pest 4 requires symfony/console 7.4, so Composer downgraded it from 8.0.11 to 7.4.22.
- Rector 2.8.0 crashed on 27 files with phpstan/phpdoc-parser 2.4.0 (pulled in by Pest). Rector 2.8.3 fixes this; do not go below it.
- On Windows, `composer require "vendor/pkg:^x"` from PowerShell goes through `composer.bat`, and cmd strips the `^`. Edit the constraint in composer.json and run `composer update vendor/pkg --with-dependencies`, or call `php C:\ProgramData\ComposerSetup\bin\composer.phar` directly.
- `composer require`/`update` exit with code 1 because the post-install audit reports pre-existing advisories (41 advisories in 11 packages). The install itself succeeds.

## Running the tools

On Windows, call the tools through `php vendor/bin/<tool>`.

| Command | What it does |
| --- | --- |
| `php artisan test` | Runs the full suite (Pest is the runner) |
| `php artisan test --order-by=random` | Same, in random order |
| `php vendor/bin/pest` | Runs the suite with Pest directly |
| `php vendor/bin/phpstan analyse --memory-limit=1G` | Static analysis (level 6 + Larastan) |
| `php vendor/bin/pint` | Fixes code style |
| `php vendor/bin/pint --test` | Checks code style without writing |
| `php vendor/bin/rector --dry-run` | Shows what Rector would change; non-zero exit if anything |
| `php vendor/bin/rector` | Applies Rector changes |

Composer scripts (`composer list` shows the descriptions):

| Script | Runs |
| --- | --- |
| `composer lint` | `pint --test` |
| `composer format` | `pint` |
| `composer analyse` | `phpstan analyse --memory-limit=1G --no-progress` (no process timeout) |
| `composer refactor` | `rector --dry-run` |
| `composer test` | `php artisan config:clear` then `php artisan test` (no process timeout) |
| `composer quality` | `lint`, `analyse`, `refactor`, `test` in sequence; stops at the first failure. Run it locally before pushing; no CI workflow runs it today (see [CI workflow](#ci-workflow)) |

Run test commands alone. Running two artisan/test commands in parallel against the same environment caused spurious SQLite transaction failures during Phase 3.

As of 2026-11-01 the suite has 1063 passing tests (5129 assertions) and 15 skipped, in about 100 seconds.

## Pest and PHPUnit coexistence

- `tests/Pest.php` applies `Tests\TestCase` and `RefreshDatabase` to Pest closure tests under `tests/Feature`.
- The existing class-based PHPUnit tests are unchanged. They extend `Tests\TestCase` and declare their own traits; Pest runs them as-is, and `php vendor/bin/phpunit` still runs them too.
- New tests are written in Pest syntax (for example `tests/Feature/AccountDeletionTest.php`, `tests/Feature/RecurringPlanCapTest.php`, `tests/Feature/Billing/CheckoutSuccessStatusTest.php`, `tests/Feature/Billing/PastDueCancelTest.php`).
- Pest helper functions are global. The current ones are `visitSuccess`, `pastDueSubscription`, `userWithDailySeries` and `marchCount`; new Pest files must not reuse these names.
- `phpunit.xml` forces SQLite `:memory:`, and `Tests\TestCase` seeds `RolesAndPermissionsSeeder` and calls `withoutVite()`. Stripe is always faked with `Tests\Fakes\FakeStripeGateway`; no test calls Stripe.

## PHPStan (level 6 + Larastan)

Configuration: `phpstan.neon`.

- Includes `vendor/larastan/larastan/extension.neon` and `phpstan-baseline.neon`.
- Paths: `app`, `config`, `database`, `routes`. Excluded: `vendor`, `storage`, `bootstrap/cache`.
- `tmpDir: storage/framework/cache/phpstan` (git-ignored).
- `parseModelCastsMethod: true`. Models declare casts in a `casts()` method typed `array<string, string>`. Without this option Larastan ignores those casts and types attributes from the migrations, so enums and dates were typed as strings and produced about 25 false positives.

Why level 6: it checks types of parameters, returns and properties, and requires generic and iterable value types. That catches real mistakes in the service/repository layer without the large amount of `mixed` handling that levels 7 to 9 demand from Eloquent-heavy code. Raising the level is possible later, one step at a time.

The first run reported 286 errors, mostly missing generics on relations (131) and missing iterable value types (63). They were fixed with docblocks (relation generics, `Builder<self>` on scopes, `Collection<int, Model>`/`LengthAwarePaginator` on repository and service interfaces, `array<string, mixed>` on DTOs and Form Requests) and a few behaviour-preserving code changes.

### Baseline

`phpstan-baseline.neon` has 3 entries, all `nullCoalesce.property` for the same line: `$this->filterable ?? []` in `App\Traits\Filterable::getFilterableFields()`. PHPStan reports it once per model that uses the trait (`Goal`, `Transaction`, `Wallet`). The fallback protects a model that uses the trait without declaring `$filterable`, so removing it would change behaviour. Declaring the property in the trait is a fatal error, because the models define it with their own defaults. PHPStan attributes trait errors to the using class's file, so an inline `@phpstan-ignore` in the trait does not match. There are no inline ignores in the code.

## Pint

Configuration: `pint.json`.

- Preset: `laravel`.
- Excluded: `vendor`, `storage`, `bootstrap/cache`, `node_modules`, `public`, and every `*.blade.php` file. Blade templates are never formatted by Pint; edit them by hand.
- Pint checks 232 files.

## Rector

Configuration: `rector.php`.

- Paths: `app`, `config`, `database`, `routes`. Tests, Blade views, vendor and storage are not listed, so Rector never touches them.
- `database/migrations` is skipped. Migrations are records of changes that have already run; they must not be rewritten.
- `withPhpSets()` without arguments derives the PHP level from composer.json (`^8.3`). The `php85` set is not used, even though development runs on PHP 8.5. composer.lock requires PHP >= 8.4 (spatie/laravel-activitylog 5.x and Symfony 8 components), so the app cannot run on 8.3 anyway; staying at the composer.json `^8.3` level is a deliberately conservative choice so Rector does not rewrite working code into 8.4/8.5-only syntax. Raising the level is a separate decision.
- Prepared sets: `deadCode`, `codeQuality`, `typeDeclarations`. These are conservative: they remove dead code, simplify expressions and add types Rector can prove from the code.
- `LaravelSetList::LARAVEL_120`: Laravel 12 upgrade rules (the app moved from Laravel 13 to 12, see [docs/maintenance/laravel-12-downgrade.md](../maintenance/laravel-12-downgrade.md)). `ScopeNamedClassMethodToScopeAttributedClassMethodRector` is skipped so the `scopeXxx()` methods are not rewritten to the `#[Scope]` attribute.
- `withImportNames(importShortClasses: false, removeUnusedImports: true)`: imports fully qualified names and removes unused imports, but leaves global classes such as `\Exception` alone.

Skipped rules:

- `ReadOnlyPropertyRector` and `ReadOnlyClassRector`. `readonly` breaks Laravel patterns that assign after construction (container setters, model hydration, test doubles that extend a class).

No other rule was skipped. Every diff from the first run (119 files) was reviewed for behaviour changes and none was found. The main rules applied were `SafeDeclareStrictTypesRector` (64 files; it adds `declare(strict_types=1)` only where every call, return and property assignment is proven to pass exact native types), arrow function and closure return types, `instanceof` checks on natively nullable objects, `compact()` replaced by explicit arrays, and typed private constants.

Rector runs before Pint; Pint then normalises whatever Rector prints.

## CI workflow

> **Status (2026-11-01):** `.github/workflows/quality.yml` was deleted from the working tree (not yet committed at the time of writing). The only workflow left is `.github/workflows/deploy.yml`, a plain FTP upload with no tests, quality checks or build step. Run `composer quality` locally. The section below describes the removed workflow and is kept as the recipe to restore it from git history (`git show HEAD:.github/workflows/quality.yml`).

`.github/workflows/quality.yml` ("Quality") ran on every push and pull request on all branches. A concurrency group per ref cancels older runs of the same ref.

The single job on `ubuntu-latest`:

1. Checks out the code (`actions/checkout@v4`).
2. Installs PHP 8.5 with `mbstring, dom, fileinfo, intl, gd, bcmath, pdo_sqlite, sqlite3`, no coverage, Composer v2 (`shivammathur/setup-php@v2`).
3. Installs Node 22 with the npm cache (`actions/setup-node@v4`).
4. `composer install --no-interaction --prefer-dist --no-progress`.
5. `cp .env.example .env && php artisan key:generate`.
6. `npm ci` and `npm run build`.
7. `composer quality` with `DB_CONNECTION=sqlite`.

The workflow uses no secrets and has read-only repository permissions. Tests use SQLite `:memory:` through `phpunit.xml` and fake Stripe, so CI needs no database service or Stripe key. The FTP deploy workflow (`.github/workflows/deploy.yml`) is separate; see "Deploy" in the README.

## Notable fixes found by the tools

PHPStan:

- Larastan ignored every model cast until `parseModelCastsMethod` was enabled (see above).
- Missing relation and collection generics hid property and method errors in `ReportController`, `BudgetService`, `TransactionService`, `WalletSharingService`, `WalletController` and `AiController`. Typing the repository and service interfaces cleared them.
- `ReportController::export()` gained the native return type `RedirectResponse|Response`; it already returned one or the other.
- `ClaimWalletInvitations` now checks `$event->user instanceof User`. `Verified::$user` is typed `MustVerifyEmail`, and `App\Models\User` is the only implementation, so the branch always runs.
- `Subscription::$status` and `PaymentHistory::$status` have `@property ...|null` docblocks: the columns are NOT NULL with database defaults, but unsaved instances have a null status.

Rector:

- `rand()` became `random_int()` in `SubscriptionFactory`.
- `DB::transaction` closures got the return type their enclosing method already declares.
- Unused imports were removed (for example `Request` in `SimulatorController`).
