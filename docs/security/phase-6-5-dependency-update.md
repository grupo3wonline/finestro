# Phase 6.5: PHP dependency security update

Date: 2026-10-31. Scope: clear every advisory reported by `composer audit`, fix the visible formula-guard apostrophe in XLSX exports, and add dependency audits to CI.

## Result

| Check | Before | After |
|---|---|---|
| `composer audit` | 41 advisories in 11 packages (1 critical) | No advisories |
| `npm audit --omit=dev` | 0 vulnerabilities | 0 vulnerabilities |
| Major version bumps | - | None |
| Packages removed | - | None |
| Packages added | - | `symfony/polyfill-time` v1.42.0 (new dependency of Symfony 8.1) |

`composer audit` still lists one abandoned package, `symplify/rule-doc-generator-contracts`. It is not a security advisory. It is a dev-only dependency of `driftingly/rector-laravel` (Rector rules) and is never loaded by the application.

## How the update was done

Only the affected packages and their dependencies were updated, with temporary `--with` pins that keep each package on its current major line:

```bash
composer update laravel/framework league/commonmark "guzzlehttp/*" "symfony/*" "league/flysystem*" brick/math \
  --with-dependencies \
  --with "guzzlehttp/guzzle:~7.15.2" --with "guzzlehttp/psr7:~2.13" \
  --with "guzzlehttp/promises:~2.0" --with "guzzlehttp/uri-template:~1.0" \
  --with "brick/math:~0.14.8" --with "symfony/console:~7.4.14"
```

Without the pins, Composer resolved Guzzle 8, psr7 3, promises 3, uri-template 2, brick/math 1.0 and symfony/console 8.1, and removed `ralouphie/getallheaders`. None of those majors is needed: Laravel 13.39 accepts `guzzlehttp/guzzle ^7.8.2 || ^8.0` and `brick/math ^0.14.2 ... || ^1.0`, and Guzzle 7.15.x is the patched 7.x line. Guzzle 7.15.2+ requires `guzzlehttp/psr7 ^2.13`, so psr7 moved to 2.13.2 (still 2.x).

Windows note: `composer` runs through a `.bat` file, where `cmd` strips the `^` character. The pins therefore use `~` constraints, which mean the same here (`~2.13` is `>=2.13 <3`).

`composer.json` change: `laravel/framework` constraint raised from `^13.8` to `^13.39`, so a fresh resolve can never pick a vulnerable 13.x release. No other root constraint changed. Guzzle, psr7, Symfony, commonmark and Flysystem are transitive dependencies and stay governed by the lock file and the CI audit.

First-party packages (Filament 5.11.0, Livewire, Sanctum 4.3.3, Scout 11.9.0, L5-Swagger 11.2.0, Spatie permission/activitylog/medialibrary/laravel-data, maatwebsite/excel 4.0.6, dompdf 3.1.2, stripe-php 20.1.0, Pest/PHPUnit/Larastan/Rector/Pint) did not need an update and are unchanged.

## Version changes

All production dependencies. Every change is a patch or minor release on the same major line.

| Package | Before | After |
|---|---|---|
| laravel/framework | v13.9.0 | v13.39.0 |
| laravel/prompts | v0.3.17 | v0.3.26 |
| laravel/serializable-closure | v2.0.13 | v2.1.0 |
| guzzlehttp/guzzle | 7.10.0 | 7.15.6 |
| guzzlehttp/promises | 2.3.0 | 2.6.0 |
| guzzlehttp/psr7 | 2.9.0 | 2.13.2 |
| guzzlehttp/uri-template | v1.0.5 | v1.0.11 |
| league/commonmark | 2.8.2 | 2.10.7 |
| league/flysystem | 3.34.0 | 3.36.0 |
| league/flysystem-local | 3.31.0 | 3.35.3 |
| league/mime-type-detection | 1.16.0 | 1.17.0 |
| league/uri | 7.8.1 | 7.9.1 |
| league/uri-interfaces | 7.8.1 | 7.9.1 |
| symfony/clock | v8.0.8 | v8.1.9 |
| symfony/css-selector | v8.0.9 | v8.1.10 |
| symfony/error-handler | v8.0.8 | v8.1.10 |
| symfony/event-dispatcher | v8.0.9 | v8.1.5 |
| symfony/http-foundation | v8.0.8 | v8.1.10 |
| symfony/http-kernel | v8.0.11 | v8.1.10 |
| symfony/mailer | v8.0.8 | v8.1.10 |
| symfony/mime | v8.0.9 | v8.1.10 |
| symfony/routing | v8.0.9 | v8.1.10 |
| symfony/translation | v8.0.10 | v8.1.10 |
| symfony/uid | v8.0.9 | v8.1.9 |
| symfony/var-dumper | v8.0.8 | v8.1.10 |
| symfony/polyfill-intl-idn | v1.37.0 | v1.43.0 |
| symfony/polyfill-php84 | v1.37.0 | v1.43.0 |
| symfony/polyfill-php86 | v1.37.0 | v1.43.0 |
| symfony/polyfill-uuid | v1.37.0 | v1.43.0 |
| symfony/polyfill-time | (none) | v1.42.0 |
| nesbot/carbon | 3.11.4 | 3.15.1 |
| carbonphp/carbon-doctrine-types | 3.2.0 | 3.2.1 |
| monolog/monolog | 3.10.0 | 3.12.1 |
| vlucas/phpdotenv | v5.6.3 | v5.8.0 |
| phpoption/phpoption | 1.9.5 | 1.10.0 |
| graham-campbell/result-type | v1.1.4 | v1.2.0 |
| ramsey/uuid | 4.9.2 | 4.9.4 |
| doctrine/inflector | 2.1.0 | 2.1.1 |
| doctrine/lexer | 3.0.1 | 3.0.3 |
| dragonmantank/cron-expression | v3.6.0 | v3.6.1 |
| masterminds/html5 | 2.11.0 | 2.12.0 |
| nette/schema | v1.3.5 | v1.4.0 |
| nette/utils | v4.1.4 | v4.1.6 |

Unchanged on purpose: `symfony/console` v7.4.22 (pinned to 7.4; no advisory), `brick/math` 0.14.8 (no advisory), `ralouphie/getallheaders` 3.0.3 (kept, still required by psr7 2.x).

## Advisories fixed

| Package | Severity | Advisory | Affected | Fixed by |
|---|---|---|---|---|
| laravel/framework | critical | CVE-2026-114509 TokenGuard authentication bypass via non-string API credentials | >=13.0.0,<13.29.0 | 13.39.0 |
| laravel/framework | high | CRLF injection in default `email` rule (incl. CVE-2026-48019) | <=13.9.0 / <13.10.0 | 13.39.0 |
| laravel/framework | medium | CVE-2026-114508 relationship inclusion can trigger unauthorized data destruction | >=13.0.0,<13.27.0 | 13.39.0 |
| laravel/framework | medium | Temporary signed URL path confusion | >=13.0.0,<13.12.0 | 13.39.0 |
| laravel/framework | low | CVE-2026-102279 XSS in debug page information | >=13.0.0,<13.30.0 | 13.39.0 |
| league/commonmark | high (9), medium (3) | 12 advisories: DoS in GFM tables, Attributes, SmartPunct, code fences, footnotes, heading slugs, nested XML; XSS via `on*` filter bypass; unsafe-link filter bypass (CVE-2026-71478); DisallowedRawHtml bypass; CVE-2026-71488 | up to <=2.10.1 | 2.10.7 |
| guzzlehttp/guzzle | high (1), medium (8) | 9 advisories: CVE-2026-69246 noncanonical host bypass, CVE-2026-69245, CVE-2026-67354, CVE-2026-67355, CVE-2026-67353, CVE-2026-59883, CVE-2026-67339, CVE-2026-55767, CVE-2026-55568 | <7.15.2 | 7.15.6 |
| guzzlehttp/psr7 | medium | CVE-2026-59882, CVE-2026-55766, CVE-2026-49214, CVE-2026-48998 (host confusion, CRLF injection) | <2.12.3 | 2.13.2 |
| symfony/http-foundation | high, medium | CVE-2026-108082 ReDoS in `HeaderUtils::split()`; CVE-2026-48736 `IpUtils::PRIVATE_SUBNETS` IPv6 forms | <8.1.7 / <8.0.13 | 8.1.10 |
| symfony/http-kernel | high | CVE-2026-45075 HEAD bypasses `methods: ['GET']` in `#[IsGranted]` | <8.0.12 | 8.1.10 |
| symfony/mime | high, medium | CVE-2026-45067 CRLF in `Address`; CVE-2026-45070 header injection via parameter names | <8.0.12 / <8.1.10 | 8.1.10 |
| symfony/mailer | medium | CVE-2026-45068 argument injection in `SendmailTransport` | <8.0.12 | 8.1.10 |
| symfony/routing | medium | CVE-2026-48784, CVE-2026-45065 `UrlGenerator` dot-segment / requirement bypass | <8.0.13 | 8.1.10 |
| league/flysystem | low | CVE-2026-102601 `WhitespacePathNormalizer` control-character check bypass | <=3.35.2 | 3.36.0 |
| symfony/polyfill-intl-idn | low | CVE-2026-46644 `xn--` labels decoding to ASCII | <1.38.1 | 1.43.0 |

Remaining advisories: none.

## Code changes required by the upgrade

None. Laravel 13.39, Symfony 8.1, Guzzle 7.15 and commonmark 2.10 needed no application change: Pint, PHPStan level 6 (baseline unchanged, 3 entries) and Rector pass, and the full test suite passes. No package shipped a migration (`php artisan migrate:status --pending`: no pending migrations).

## Refresh steps after the update

- `post-autoload-dump` ran `php artisan filament:upgrade`, which republished the Filament JS/CSS/fonts into `public/js/filament`, `public/css/filament` and `public/fonts/filament` and cleared config, route and view caches.
- `php artisan l5-swagger:generate`, `php artisan view:cache` / `view:clear`, `php artisan filament:optimize` / `filament:optimize-clear`, `php artisan route:list` (175 routes) and `npm run build` succeed.
- Livewire assets stay unpublished: Livewire serves `livewire.js` from its own route, so the script always matches the installed version. (A trial `livewire:publish --assets` created `public/vendor/livewire`; that copy was removed again so it cannot go stale on the next upgrade.)
- Admin panel check with `php artisan serve`: `/admin/login` returns 200 and every referenced asset (Filament `app.css`, Inter font CSS, `app.js`, `echo.js`, `support.js`, `actions.js`, `notifications.js`, `schemas.js`, `tables.js`, `password-reveal.js`, and `livewire.js`) returns 200.

## XLSX formula guard (cosmetic fix)

Before: `EscapesFormulaInjection::text()` prefixed text starting with `=`, `+`, `-`, `@`, tab or CR with an apostrophe in both writers. In XLSX the apostrophe was visible, because the cell already is a string.

After:

- `TransactionsExport` and `AdminPaymentsExport` take the writer type as a second constructor argument (`Maatwebsite\Excel\Excel::CSV` by default, so the guarded behaviour is the safe default).
- CSV: unchanged, the apostrophe guard stays (CSV has no cell types).
- XLSX: the text is kept as-is. Both exports extend `StringValueBinder` with formula conversion on, so every cell is written with `setValueExplicit(..., TYPE_STRING)`. The worksheet XML has no `<f>` element and the spreadsheet never evaluates the value.
- Callers pass the writer type: `ReportService::exportCsv()` / `exportXlsx()` and the admin `ListPaymentHistories` header actions.

Tests added:

- `tests/Feature/ReportExportTest.php`: user-app XLSX download with `=1+1`, `+1+1`, `-1+1`, `@SUM(1+1)` and `=HYPERLINK(...)`. The cell value equals the original text, the data type is `s` (string), `isFormula()` is false, no guarded copy exists, and `sheet1.xml` contains no `<f>` element. The existing CSV guard test is unchanged.
- `tests/Feature/Admin/BillingResourcesTest.php`: admin XLSX written through `Excel::raw()` (name, e-mail, description cells are the original text, type string, no formula, no `<f>` in the sheet XML) and the CSV of the same data keeps the guard; the `exportCsv` / `exportXlsx` header actions build the export for their own writer (`'=1+1` vs `=1+1`).

## CI dependency audit

`.github/workflows/quality.yml` has two new steps that fail the build on regressions:

- After `composer install`: `composer audit --locked --no-interaction --abandoned=report`. It fails on any security advisory in the locked packages. Abandoned packages are reported but do not fail (see the note on `symplify/rule-doc-generator-contracts` above).
- After `npm ci`: `npm audit --omit=dev --audit-level=high`. It fails on high or critical advisories in production npm dependencies.

## How to revert

The pre-update files are in `.agents/tasks/phase-6-5-security/backup/` (`composer.json`, `composer.lock`, plus `audit-before.json` and `show-before.json` for reference).

```powershell
Copy-Item .agents/tasks/phase-6-5-security/backup/composer.json, .agents/tasks/phase-6-5-security/backup/composer.lock . -Force
composer install
php artisan optimize:clear
```

Reverting restores the 41 advisories listed above. The XLSX change and the CI steps are independent of the dependency versions; revert them separately (`app/Exports/*`, `app/Services/ReportService.php`, `ListPaymentHistories.php`, `.github/workflows/quality.yml`) if needed.
