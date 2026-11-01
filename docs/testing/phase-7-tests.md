# Phase 7: Navigation, flow, security and load tests

All automated tests run with `php artisan test` (Pest 4 on PHPUnit 12, SQLite
in memory, see `phpunit.xml`). The load tests use k6 and run by hand.

## Results

| Run | Passed | Skipped | Assertions |
| --- | ---: | ---: | ---: |
| Baseline before phase 7 | 860 | 15 | 3740 |
| End of phase 7 (after FEAT-005) | 981 | 15 | 4584 |
| After review fixes (iteration 2) | 1001 | 15 | 4824 |

Review fixes added 18 tests: `PolicyVersionGateTest` (data rights routes open
without accepting a new version, safeguards kept, every other gated route
redirects), `DataExportTest` (job `failed()`, stale Pending rule, purge marks
stale rows Failed), `ShellTest` (agency capacity line only with a positive
`MARKETING_CTA_MONTHLY_SLOTS`), `SeoTest` (llms.txt limits from
`User::freePlanLimits()`) and `TripleCredentialLoginTest` (`seu_usuario`
placeholder).

`php artisan test --filter="Crawler|UserJourney|Hardening"`: 28 passed,
242 assertions, about 10 s. The full suite takes about 100 s on the development
machine. The 15 skips are pre-existing matrix cases, unchanged in phase 7.

Phase 7 test files: `Public/ShellTest`, `ErrorPagesTest`, `Auth/TripleCredentialLoginTest`,
`Auth/PolicyVersionGateTest`, `Privacy/ConsentTest`, `Privacy/DataExportTest`,
`Privacy/PrivacyPagesTest`, `Seo/SeoTest`, `Performance/QueryBudgetTest`, and the
three below.

## Navigation crawler

`tests/Feature/Navigation/CrawlerTest.php` crawls the app breadth-first, once per
actor: **guest**, **Free**, **Profissional** and **admin** (the `admin` role in the
user app).

- **Seeds**: every named GET route without parameters and without the `signed`
  middleware, plus the actor's own records (credit-card wallet show/edit/invoice,
  checking wallet, transaction show/edit with a receipt, goal show/edit, category
  edit, and a ready data export through its signed download URL).
- **Per response**: seeds must not answer 404 or 5xx (a guest-only page may
  redirect a signed-in user); every discovered internal link must answer
  200, 301, 302, 401 or 403. Redirect targets are queued too.
- **Per HTML page**: `role="banner"` header, the exact footer text
  `Sistema desenvolvido pelo desenvolvedor HOTWYL | WILLFROMBRASIL`,
  `data-cta="3wonline"` (Grupo 3W Online CTA), no `<iframe`, no `freshdesk`.
- **Links** (parsed with `DOMDocument`): `#…` and `javascript:` are ignored;
  `mailto:` must hold a valid address; `tel:` must be a phone number;
  `wa.me` links must match `^https://wa\.me/5541996924829(\?text=…)?$`;
  `www.3wonline.com.br` links must be https with `target="_blank"` and
  `rel` containing `noopener`; other external links must be valid https URLs
  (never fetched).
- **Forms**: every form with a literal `action` must match a route for its
  method (including the `_method` override). Forms are never submitted. Forms
  without an `action` attribute are bound by Alpine (`:action`) and skipped.
- **Dedup**: the visited key is the path plus the *names* of the query
  parameters, so month navigation (`?month=4`, `?month=3`, …) is crawled once
  instead of forever. Cap: 400 URLs per actor (the test fails if it is reached).
- Also asserted: guests see no `<form` on `/` and `/contato`; `/sobre`,
  `/servicos`, `/ferramentas` answer 301 to their landing anchors.

Visited URL keys in the last run: guest 58, Free 77, Profissional 81, admin 78.
Set `CRAWLER_DUMP=1` to print each URL with its status.

**Excluded and why**

| Excluded | Reason |
| --- | --- |
| Route names `filament.*` (`/admin`) | Filament panel with its own guard and MFA; covered by `tests/Feature/Admin/*`. |
| `api.*`, `l5-swagger.*`, `sanctum.*` | JSON API and its docs; covered by `tests/Feature/Api/*`. |
| `livewire.*`, `storage.*`, `ignition.*` | Framework endpoints, not pages. |
| `signed` routes as seeds (`verification.verify`, `settings.privacy.download`) | Need a signature; the export download is seeded with a real signed URL instead. |
| `subscription.success` as a seed | Needs Stripe's `?session_id=`; covered by `Billing/CheckoutSuccessTest` and the upgrade journey. |
| Shell checks on `reports.pdf`, `reports.print`, `reports.export`, `transactions.receipt`, `settings.privacy.download`, `robots`, `sitemap`, `llms` | Non-HTML downloads or print-only pages without the site header/footer. Their status is still checked. |

Links into excluded areas (for example `/api/documentation` for the admin) are
requested once and their status checked, but not expanded.

## End-to-end journeys

`tests/Feature/Flows/UserJourneyTest.php`:

1. Register (honeypot empty, terms accepted) → dashboard redirects to the
   verification notice → signed verification URL → dashboard 200 (current terms
   version) → logout → login with username + e-mail + password → dashboard 200.
2. Password reset: `Notification::fake()`, token from `QueuedResetPassword`,
   reset form, old password refused, new one accepted.
3. Profissional: create wallet → pending transaction (balance unchanged) → pay
   (balance reduced) → `reports.export` as CSV and XLSX.
4. Free → Stripe checkout (`FakeStripeGateway`) → success URL → Profissional →
   cancel at period end (still Profissional).
5. With GTM/GA/Pixel IDs configured: no vendor URL before consent; after
   `POST /consentimento` with analytics only, the GTM `<noscript>` appears.
6. Data export (signed download) → account deletion with password → guest,
   soft-deleted, login refused.

## Security hardening

`tests/Feature/Security/HardeningTest.php`:

| Area | Checks |
| --- | --- |
| Enumeration | Unknown account, wrong password and username/e-mail of different users all get the same `login` error (`auth.failed`) and no field-level error. `POST /forgot-password` gives the same neutral `status` message for an unknown e-mail, a registered one and a broker-throttled repeat, with no errors. |
| Rate limits | 6th failed login for the same username + e-mail + IP is throttled; 7th `POST /register` and 7th `POST /forgot-password` within a minute get 429. |
| CSRF | Laravel skips CSRF checks in unit tests, so the test binds a subclass of `PreventRequestForgery` (the Laravel 13 web-group middleware; `ValidateCsrfToken` is its legacy alias) with `runningUnitTests()` returning false. `POST /login`, `POST /consentimento` and an authenticated `PUT /configuracoes/senha` without `_token` get 419; a cross-site `Sec-Fetch-Site` request gets 419; a request with the session token passes. |
| XSS | `<script>alert(1)</script>` as wallet name and transaction description is escaped on `wallets.index`, `transactions.index` and the dashboard. |
| CSV export | Served as `text/csv` attachment with `nosniff`; a `=HYPERLINK(...)` description is exported as `'=HYPERLINK(...)` and no cell starts with `=`. |
| SQL injection | Payloads such as `' OR 1=1 --` and `1; DROP TABLE users` in `search` return 200 and only own rows; the same payload in structured filters (`type`, `status`, `wallet_id`, `category_id`, `payment_method`, `year`) is rejected by validation with a redirect; the `users` table and row count are intact. |
| Mass assignment | Registration ignores `role`, `is_admin`, `suspended_at`, `email_verified_at`; a posted `user_id` on wallet creation is ignored. |
| Headers | `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy` and a CSP with `default-src 'self'`, `frame-ancestors 'self'`, `object-src 'none'` on `/` and `/dashboard`. |

Not duplicated: API per-IP limits (`Api/ApiIpRateLimitTest`), IDOR matrices
(`Authorization/*`, `DataIsolationTest`), admin CSP and access (`Admin/*`).

**Password-reset enumeration (fixed in the final integration step, approved by
the product owner)**: `POST /forgot-password` used to answer an unknown e-mail
with a validation error and a known one with a success status, which let
someone check whether an e-mail has an account. `PasswordResetLinkController`
now ignores the broker result and always shows `passwords.sent`, "Se o e-mail
estiver cadastrado, você receberá um link de redefinição em instantes.", for
sent, unknown-user and broker-throttled requests. Only a malformed e-mail still
gets a field error. The 6/min route throttle is unchanged. HardeningTest checks
the three cases and that only one link is sent.

## Query budgets

From `tests/Feature/Performance/QueryBudgetTest.php` (SQLite in memory, after a
warm-up request that loads the permission registry):

| Page | Measured queries | Budget |
| --- | ---: | ---: |
| Landing (guest) | 0 | 2 |
| Dashboard | 18 with 5 and with 25 transactions | 22 |
| Transactions index | 14 with 5 and with 25 transactions | 17 |

## Load tests (k6)

Scripts in `tests/load/` (how-to in [tests/load/README.md](../../tests/load/README.md)):

| Script | Profile | Thresholds |
| --- | --- | --- |
| `smoke.js` | 1 VU, 30 s | failed < 1 %, p95 < 800 ms |
| `load.js` | ramp to 20 VUs, ~5 min | failed < 1 %, p95 < 800 ms |
| `stress.js` | up to 100 VUs, ~8 min | failed < 5 %, p95 < 2000 ms |
| `spike.js` | burst to 150 VUs for 30 s | failed < 10 %, p95 < 3000 ms |

Flows: landing, login (CSRF token read from the page, username + e-mail +
password from `K6_USERNAME`/`K6_EMAIL`/`K6_PASSWORD`), dashboard, and
`GET /api/v1/wallets` with `K6_API_TOKEN`. `lib/config.js` aborts unless the host
is local (`localhost`, `127.0.0.1`, `*.test`, `*.local`) or `K6_ALLOW_STAGING=1`.

**k6 is not installed on the development machine (`Get-Command k6` found
nothing), so the load tests were not run.** The scripts were only
syntax-checked with `node --check`. No numbers are reported for them.
