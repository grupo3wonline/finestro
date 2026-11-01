# Finestro

Personal finance micro SaaS for freelancers, small business owners and
professionals. Each user sees only their own wallets, transactions, goals,
categories and budgets; access is controlled by roles and permissions (ACL).
The interface is in Brazilian Portuguese; code and docs are in English.

## Stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 13, PHP 8.5 (composer constraint `^8.3`), MySQL 8 |
| Front end | Blade, Alpine.js, Tailwind CSS 3, Vite 5, Font Awesome, SweetAlert2 |
| Auth | Laravel Breeze (session), e-mail verification, password reset |
| ACL | spatie/laravel-permission (`user`, `admin` roles) + policies, per-user query scoping (`BelongsToUser`) |
| Admin panel | Filament 5 at `/admin` (mandatory MFA) |
| API | REST v1 at `/api/v1` with Laravel Sanctum personal tokens, OpenAPI docs via L5 Swagger at `/api/documentation` |
| Data | spatie/laravel-data DTOs, Spatie Media Library (receipts), Spatie Activitylog, Laravel Excel, Scout, queues and cache |
| Billing | Stripe Checkout and webhooks |
| Quality | Pest 4 / PHPUnit 12, Pint, PHPStan level 6 + Larastan, Rector |

Architecture: `Controller → Service (interface) → Repository (interface) → Model`,
with FormRequests for validation, DTOs between layers and bindings in
`app/Providers/AppServiceProvider.php`.

## Plans

| Plan | What it includes |
| --- | --- |
| **Básico** (`free`) | 1 wallet, 100 transactions/month, 2 goals, 5 categories, 3 budgets/month, dashboard, financial calculators, profile tests, currency quotes |
| **Profissional** (`professional`) | Everything unlimited, wallet sharing, simulators, reports with CSV/XLSX/PDF export, AI features, REST API, priority support |

Limits live in `User::getPlanLimitsAttribute()`. Prices are not hard-coded:
they come from the `STRIPE_*` settings through `PlanPricing`
(`App\Interfaces\Services\PlanPricingInterface`), and a test fails if a price
literal appears in a view.

## Setup

Requirements: PHP 8.3+ (8.5 used in development) with `gd`, `zip`, `intl`;
Composer 2; MySQL 8; Node.js 20+.

```bash
composer setup          # composer install, .env, key, migrate, npm install, npm run build
php artisan db:seed     # optional demo data and roles
```

Edit `.env` before or after `composer setup`:

- `DB_*`, `APP_URL`, `MAIL_*`.
- `STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY`, `STRIPE_WEBHOOK_SECRET`,
  `STRIPE_PROFESSIONAL_PRICE_ID`, `STRIPE_PROFESSIONAL_PRICE` (use test keys
  outside production and never commit them).
- `LEGAL_CONTROLLER_NAME`, `LEGAL_CONTROLLER_CNPJ`, `LEGAL_CONTROLLER_ADDRESS`,
  `PRIVACY_DPO_EMAIL` (shown in the privacy policy).
- Optional, consent-gated: `GOOGLE_TAG_MANAGER_ID`, `GOOGLE_ANALYTICS_ID`,
  `FACEBOOK_PIXEL_ID`.

Production also needs the scheduler (recurring transactions, log and export
purges, token pruning) and a queue worker (e-mails, data exports):

```bash
* * * * * cd /path/to/finestro && php artisan schedule:run >> /dev/null 2>&1
php artisan queue:work --tries=1
```

Demo account after seeding: username `demo`, e-mail `demo@finestro.com.br`,
password `password`.

## Common commands

```bash
composer dev            # server + queue + logs + Vite
composer quality        # pint --test, phpstan, rector --dry-run, php artisan test
php artisan test        # Pest suite (SQLite in memory)
npm run build           # production assets
php artisan migrate     # additive migrations
composer format         # fix code style with Pint
```

Load tests use k6 and are run by hand; see [tests/load/README.md](tests/load/README.md).

## Signing in

| Where | Credentials |
| --- | --- |
| App (`/login`) | **username + e-mail + password** of the same account. Every mismatch gets the same generic error; 5 failed attempts per username + e-mail + IP lock the pair for a minute. |
| Admin panel (`/admin/login`) | E-mail + password, then **mandatory MFA** (TOTP authenticator app), on a separate guard. Requires a verified, non-suspended account with the `admin.access` permission. |
| API (`/api/v1`) | `Authorization: Bearer <token>` with a Sanctum personal token created in **Configurações > Tokens de API** (Profissional plan). |

## Documentation

| Phase | Document |
| --- | --- |
| 1 | [ACL audit](docs/security/phase-1-acl-audit.md) |
| 2 | [Stripe billing](docs/billing/phase-2-stripe.md) |
| 3 | [Quality tooling](docs/quality/phase-3-tooling.md) |
| 4 | [Packages and infrastructure](docs/infrastructure/phase-4-packages.md) |
| 5 | [REST API](docs/api/phase-5-api.md) |
| 6 | [Filament admin panel](docs/admin/phase-6-filament.md) |
| 6.5 | [Dependency update](docs/security/phase-6-5-dependency-update.md) |
| 7 | [Privacy compliance (LGPD, GDPR, CCPA, POPIA)](docs/compliance/phase-7-privacy.md) |
| 7 | [SEO, GEO, AEO, SEM and performance](docs/seo/phase-7-seo.md) |
| 7 | [Tests: crawler, flows, security, load](docs/testing/phase-7-tests.md) |
| 7 | [Orphan cleanup](docs/maintenance/phase-7-cleanup.md) |

Earlier integration notes: [docs/INTEGRACAO-SISTEMAS-LEGADOS.md](docs/INTEGRACAO-SISTEMAS-LEGADOS.md).

## Credits

Sistema desenvolvido pelo desenvolvedor HOTWYL | WILLFROMBRASIL.
Developed by [Grupo 3W Online](https://www.3wonline.com.br), custom websites and systems on demand.
