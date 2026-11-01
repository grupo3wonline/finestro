# Token-only REST API v1, Scout transaction search, Swagger docs and Phase 4 fixes

Phase 5 adds a Sanctum personal-token API under `/api/v1`. It has 29 routes: account, subscription, wallets, transactions (including pay, unpay and receipt download), goals, budgets, categories and the report summary. Users manage tokens from a "Tokens de API" settings page. The API controllers reuse the existing services, policies and Form Requests. A single `ApiExceptionRenderer` puts every `api/*` error in a pt-BR `{message, code[, errors]}` envelope. Transaction search moves from a model scope to Laravel Scout's database engine, and l5-swagger generates an OpenAPI spec from PHP attributes. Part C ships with it: the Stripe live/test mode-mismatch guard and the production test-key warning (C-1), receipt files that follow commit/rollback (C-2), activity-page name resolution (C-3) and XLSX gating datasets (C-4). Recorded evidence: 672 passed / 15 skipped / 2734 assertions in both normal and random order, `composer quality` exit 0, and the PHPStan baseline still at 3 entries.

Watch for: requests without a valid token are never rate limited (confirmed); Scout search loads every matching id into a `whereIn` with no upper bound (likely); the PUT/PATCH wallet docs list fields the update request silently drops (confirmed). All three are minor.

**Verdict**: APPROVED

## High-level view

Every v1 route sits behind `auth:sanctum → verified → throttle:api → api.plan → abilities:<scope>`, and `reports/summary` also requires `permission:reports.view`. `config/sanctum.php` sets `guard => []`, so a browser session can never authenticate an API call. The foundation tests prove this with an `actingAs` request that gets 401. Isolation reuses the web pattern: a scoped lookup, then `?? abort(404)`, then a policy check. A foreign record returns 404, a missing Spatie permission returns 403 `forbidden`, a missing token ability returns 403 `missing_ability`, and no token returns 401. `ApiEndpointMatrixTest` checks every one of these cases (plus Free → `plan_upgrade_required` and 429) against all 29 registered route names. It also guards that the dataset matches the router, and it checks the foreign record row is unchanged afterwards. Admins get no ownership bypass because no `Gate::before` exists.

All responses go through explicit-allowlist Resources. `user_id`, `deleted_at`, `receipt_path`, media rows and `stripe_*` fields never appear. `company_document` is omitted for shared-wallet members, and a receipt appears only as `has_receipt` plus an authenticated `receipt_url`. The 500 path hides traces unless the app is local with debug on. CORS is limited to `api/*`, uses `CORS_ALLOWED_ORIGINS` (falling back to `APP_URL`) and never allows credentials. Swagger UI and the JSON spec are open in `local` only. In production a guest gets 404 and a non-admin gets 403, and the tests cover both the UI and the JSON route.

The rate limiter runs after authentication so its key is the user, which stops extra tokens from multiplying the quota. As a result, unauthenticated and invalid-token traffic never reaches a limiter.

Scout uses `newScoutQuery()` built on `newQuery()`, so the `user` global scope and SoftDeletes still apply to the search query. The repository escapes LIKE wildcards. The cost is a two-step query: matching ids are collected in PHP and then fed into `whereIn`.

Part C matches the plan. `isModeMismatch()` blocks account deletion on a live/test mismatch, and the plain `resource_missing` shortcut now logs at error level. `StripeConfigCheck` logs at critical level at most once a day, never logs the key, and shows the banner to admins only. `ReceiptService` defers file removal to `afterCommit`, and `TransactionService` discards files written during a call that fails. The activity page resolves wallet and category names only within the user's own rows.

<details>
<summary>Issues (3)</summary>

1. **Unauthenticated API traffic is unthrottled** (minor). Every call without a valid token runs a token lookup and is never rate limited, because `throttle:api` sits after `auth:sanctum`. The limiter's IP fallback can never run. Fix: add an IP-keyed limiter in front of `auth:sanctum` for the v1 group, or document the trade-off in `docs/api/phase-5-api.md`.
2. **Unbounded Scout id list** (minor). `TransactionRepository::searchKeys()` loads every matching id into a PHP `whereIn` and repeats this for the list and the summary. Fix: use a subquery (`whereIn('transactions.id', <builder>)`) instead of a materialized list, or cap the result size.
3. **Wallet update contract mismatch** (minor). The PUT/PATCH docs reuse the `WalletInput` schema, which lists `company_name`, `company_document`, `credit_limit`, `closing_day` and `due_day`. `UpdateWalletRequest` has no rules for those fields, so they are silently dropped and the response still returns 200. The PUT text also says "envie todos os campos obrigatórios", but both verbs accept partial bodies. Fix: either add the rules to `UpdateWalletRequest`, or give updates their own schema that leaves those fields out and say that updates can be partial.

</details>

<details>
<summary>Details</summary>

### Rate limiter placement leaves the 401 path open

The group order is `['auth:sanctum', 'verified', 'throttle:api', 'api.plan']` (`routes/api.php`). Plan decision 5 chose this order on purpose, so the limiter key is the user and a user cannot get more quota by creating more tokens. The per-user counting test confirms that part works. The side effect (confirmed) is that every request with no token, a malformed token or a revoked token fails in `auth:sanctum` before any limiter runs. For a `{id}|secret` token, each such request still costs a `personal_access_tokens` lookup. Brute force is not realistic because the secret is 40 random characters and is stored hashed. The gap is unlimited cheap 401 traffic. The `?? $request->ip()` fallback in `AppServiceProvider` is dead code. A small IP-keyed limiter in front of `auth:sanctum` (for example 120/min) would close the gap and keep the per-user quota as it is.

### Scout two-step search

`filteredQuery()` now calls `whereIn('transactions.id', $this->searchKeys($term))`. `searchKeys()` runs `Transaction::search($escaped)->query(select id)->keys()`. In Scout 11.9, `DatabaseEngine::search()` without a `limit` returns every match as hydrated models (id only). The repository then binds all of those ids into a second query. Isolation holds, because the inner query starts from `newQuery()` with the `user` scope and the test suite covers both users and soft-deleted rows. The cost grows with the number of matches (likely). A short term such as "a" on an account with tens of thousands of transactions produces one placeholder per match. MySQL rejects prepared statements with more than 65,535 placeholders. The pre-query also runs once for the list and once for the summary, because both call `filteredQuery()`. Free accounts are capped at 100 transactions a month, so the risk is limited to long-lived Professional accounts.

The left joins in `newScoutQuery()` do not filter `categories.deleted_at` or `wallets.deleted_at`. A transaction can therefore still be found by the name of a soft-deleted category or wallet that it references. The joins use the transaction's own foreign keys, so this matches only the user's own data and is not a leak.

### Wallet update schema

`WalletController::update` (API) passes `UpdateWalletRequest::validated()` into `WalletDTO::fromArray()`. That request validates only name, type, ownership, balance, currency, color, icon, description and is_active. The OpenAPI `WalletInput` schema, which both the PUT and PATCH operations reference, also lists the company and credit-card fields. A client that sends `credit_limit` gets 200 with the old value (confirmed). The web edit form does not post those fields, so the web behaviour is consistent. Only the API contract is wrong.

### Docs routes render the API envelope

`/api/documentation` matches `api/*`, so in production `EnsureApiDocsAccess` returns its 404 and 403 as JSON envelopes rather than the styled error pages. FEAT-005 documents this, and it only affects guests and non-admins. `/docs/asset/{asset}` and `/api/oauth2-callback` stay public. They serve static swagger-ui files and the OAuth redirect page, and the generated spec is not exposed through them.

### Test coverage

The tests cover:

- the full endpoint matrix;
- the foundation envelope cases (401 malformed, expired and session-only; 403 for an unverified account; 404, 405, 429 with `Retry-After`; 500 without a trace);
- CORS: a foreign origin is never echoed and credentials are never allowed;
- exact key sets on each resource;
- receipt upload and download isolation;
- Scout scoping across two users, wildcard literals and soft-deleted rows;
- docs access in local and in production;
- the spec listing every route with 401 and 403 responses;
- every Part C behaviour.

The 15 skips come from the dataset design: 13 endpoints have no record id and 2 have no Spatie permission. They are not missing tests.

Not tested: unauthenticated request volume, Scout with very large match sets, and update payloads with fields that `UpdateWalletRequest` drops.

</details>

<details>
<summary>File map</summary>

- `routes/api.php`: v1 group, 29 routes, per-route `abilities:`, `whereUuid`.
- `bootstrap/app.php`: api routing, Sanctum/plan aliases, `ForceJsonResponse`, guest redirect bypass for `api/*`, render hook, `PlanLimitException` not reported.
- `app/Exceptions/ApiExceptionRenderer.php`, `app/Exceptions/PlanLimitException.php`: error envelope.
- `app/Http/Middleware/{ForceJsonResponse,EnsureApiAccess,EnsureApiDocsAccess}.php`: JSON negotiation, `api_access` plan gate, docs access.
- `app/Http/Controllers/Api/V1/*`: thin controllers with OpenAPI attributes.
- `app/Http/Resources/V1/*`: allowlisted resources and schemas.
- `app/Http/Requests/{Api/V1/ListRequest,TransactionIndexRequest,StoreApiTokenRequest}.php`: list, filter and token validation (the web transactions index also uses `TransactionIndexRequest`).
- `app/{DTOs/ApiTokenData,Repositories/ApiTokenRepository,Services/ApiTokenService,Http/Controllers/ApiTokenController}.php` and `resources/views/settings/tokens.blade.php`: token UI.
- `app/Enums/ApiTokenAbility.php`, `config/{api,sanctum,cors,scout,l5-swagger}.php`, `.env.example`, `routes/console.php` (prune schedule).
- `app/Models/{User,Transaction}.php`: `HasApiTokens` and `api_access` on User; `Searchable` and `newScoutQuery` on Transaction.
- `app/Repositories/TransactionRepository.php`: Scout-backed search.
- `app/Repositories/{Wallet,Goal,Category}Repository.php` and their services and interfaces: `paginate`, `namesForUser`.
- `app/Services/{StripeGateway,StripeConfigCheck,AccountDeletionService,ReceiptService,TransactionService,AccountActivityService}.php`, `resources/views/components/layout/stripe-config-alert.blade.php`: Part C.
- `app/OpenApi/*`, `storage/api-docs/.gitignore`: spec scaffolding.
- `database/migrations/2026_10_30_214820_create_personal_access_tokens_table.php`: `uuidMorphs` tokenable.
- `tests/Feature/Api/*`, `tests/Feature/{ApiTokenUi,TransactionSearch,ReceiptMedia,ActivityLog,ReportExport,AccountDeletion}Test.php`, `tests/Feature/Billing/StripeConfigCheckTest.php`, `tests/Unit/StripeGatewayResourceMissingTest.php`, `tests/Pest.php`.
- `docs/api/phase-5-api.md`, `docs/billing/phase-2-stripe.md`, `docs/infrastructure/phase-4-packages.md`.

Full diff: the tree is uncommitted. Use `git status` / `git diff HEAD` in the project root.

</details>
