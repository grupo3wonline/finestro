# Phase 5: REST API v1 (Sanctum, API Resources, L5-Swagger) and Scout search

Finestro exposes a token-only REST API under `/api/v1`. It is a Profissional plan feature. Every request is scoped to the token owner's own data, with the same services, policies, Form Requests and plan checks as the web app.

Packages added in this phase:

| Package | Constraint | Installed |
|---|---|---|
| `laravel/sanctum` | `^4.3` | 4.3.3 |
| `laravel/scout` | `^11.9` | 11.9.0 |
| `darkaonline/l5-swagger` | `^11.2` | 11.2.0 (pulls `zircote/swagger-php` 6.20.1 and `swagger-api/swagger-ui`) |

## Authentication flow

1. A Profissional user opens **Configurações → Tokens de API** (`GET /configuracoes/tokens`, route `settings.tokens.index`), enters a name and ticks the abilities the token needs.
2. The plain token (`{id}|{secret}`) is shown once, right after creation (flashed with PRG, so a reload never creates a second token). Only its SHA-256 hash is stored in `personal_access_tokens.token`. The token is never logged; the activity log records `api_token_created` / `api_token_revoked` with the token name and abilities only.
3. Clients send it on every call:

   ```bash
   curl -H "Authorization: Bearer 12|AbC..." -H "Accept: application/json" http://finestro.test/api/v1/auth/me
   ```

Rules:

- **Token-only.** `config/sanctum.php` has `'guard' => []`, so a browser session cookie never authenticates an API call (no `statefulApi()`).
- **Expiration.** `SANCTUM_TOKEN_EXPIRATION` minutes (default `129600` = 90 days). Tokens are also created with `expires_at`, so the token list shows the date.
- **Pruning.** `routes/console.php` schedules `sanctum:prune-expired --hours=24` daily.
- **Revocation.** Revoking a token in the UI deletes the row (no SoftDeletes); the next request with it is 401.
- **Account deletion** deletes all of the user's tokens before the account is soft-deleted (LGPD).
- **E-mail.** The `verified` middleware applies: an unconfirmed account gets 403 "Confirme seu e-mail para usar a API.".
- **Terms.** `terms.accepted` is not applied to the API. Tokens can only be created from the web, after the terms were accepted.

## Abilities

Abilities come from `App\Enums\ApiTokenAbility` and are enforced per route with Sanctum's `abilities:` middleware. A token without the ability gets 403 `missing_ability` with the missing list in `abilities`.

| Ability | Endpoints |
|---|---|
| `account:read` | `GET auth/me`, `GET subscription` |
| `wallets:read` | `GET wallets`, `GET wallets/{wallet}` |
| `wallets:write` | `POST wallets`, `PUT/PATCH wallets/{wallet}`, `DELETE wallets/{wallet}` |
| `transactions:read` | `GET transactions`, `GET transactions/{transaction}`, `GET transactions/{transaction}/receipt` |
| `transactions:write` | `POST transactions`, `PUT/PATCH transactions/{transaction}`, `DELETE transactions/{transaction}`, `PATCH .../pay`, `PATCH .../unpay` |
| `goals:read` | `GET goals`, `GET goals/{goal}` |
| `goals:write` | `POST goals`, `PUT/PATCH goals/{goal}`, `DELETE goals/{goal}` |
| `budgets:read` | `GET budgets` |
| `budgets:write` | `POST budgets`, `PUT/PATCH budgets/{budget}`, `DELETE budgets/{budget}` |
| `categories:read` | `GET categories` |
| `categories:write` | `POST categories`, `PUT/PATCH categories/{category}`, `DELETE categories/{category}` |
| `reports:read` | `GET reports/summary` |

Abilities narrow what a token can do. They never widen the user's role: the Spatie permissions and the ownership policies still apply.

## Plan gating

- Feature key `api_access` in `User::getPlanLimitsAttribute()`: Profissional `true`, Básico (Free) `false`. `User::hasFeature('api_access')` has an explicit arm (the `match` default is `true`).
- Route middleware `api.plan` (`App\Http\Middleware\EnsureApiAccess`) returns 403 `plan_upgrade_required` for users without the feature. This also blocks downgraded users who still hold old tokens.
- The token UI shows an upgrade CTA instead of the form to Free users, who can still see and revoke old tokens.
- The store endpoints keep the web plan-limit checks (`canCreate(...)`, `canAddBudget`) and throw `PlanLimitException` (`plan_limit_reached`) with the web texts. They cannot trigger today: only Profissional users reach the API and Profissional limits are unlimited. They are kept for parity and are not tested through the API.

## Rate limits

- Limiter `api` (`AppServiceProvider::boot()`): `API_RATE_LIMIT_PER_MINUTE` requests per minute (default 60), keyed by user id. All tokens of a user share the same quota, so creating more tokens does not raise it.
- `throttle:api` runs after `auth:sanctum`, so the key is always the user.
- Limiter `api-ip`: `API_IP_RATE_LIMIT_PER_MINUTE` requests per minute (default 120), keyed `api-ip:{client ip}`. It is applied by `App\Http\Middleware\ThrottleApiByIp`, the first middleware of the `/api/v1` group, so it runs before `auth:sanctum` and also counts requests with a missing, malformed or expired token (they get 429 once the IP is over the limit instead of an unlimited stream of 401s).
- Middleware order: Laravel sorts route middleware by its priority list, which puts `AuthenticatesRequests` before `ThrottleRequests` and its subclasses, and moves listed middleware ahead of unlisted ones (an unlisted class declared first still ends up after `auth:sanctum`). So `ThrottleApiByIp` wraps `ThrottleRequests` by composition (it is not matched by the `ThrottleRequests` entry), and `bootstrap/app.php` calls `$middleware->prependToPriorityList(AuthenticatesRequests::class, ThrottleApiByIp::class)` to place it right before authentication. A second `throttle:` entry would not work. `tests/Feature/Api/ApiIpRateLimitTest.php` checks the sorted order (`Router::gatherRouteMiddleware()`) on every `api.v1.*` route.
- Caveat: clients behind one NAT or proxy share the IP quota. Behind a load balancer, configure trusted proxies so `$request->ip()` is the real client address; otherwise every client shares the balancer's IP.
- Responses carry `X-RateLimit-Limit` and `X-RateLimit-Remaining`. Over the limit: 429 `too_many_requests` with `Retry-After`.

## Endpoints

Base path `/api/v1`. Every route uses `ThrottleApiByIp`, `auth:sanctum`, `verified`, `throttle:api`, `api.plan` and the ability below. Ids are UUIDs (`whereUuid`), so a non-UUID id is 404 before any query.

| Method | Path | Ability | Spatie permission | Success |
|---|---|---|---|---|
| GET | `auth/me` | `account:read` | — | 200 |
| GET | `subscription` | `account:read` | — | 200 |
| GET | `wallets` | `wallets:read` | `wallets.view` (policy) | 200 paginated |
| POST | `wallets` | `wallets:write` | `wallets.create` (policy) | 201 |
| GET | `wallets/{wallet}` | `wallets:read` | `wallets.view` (policy) | 200 |
| PUT, PATCH | `wallets/{wallet}` | `wallets:write` | `wallets.update` (policy) | 200 |
| DELETE | `wallets/{wallet}` | `wallets:write` | `wallets.delete` (policy) | 204 |
| GET | `transactions` | `transactions:read` | `transactions.view` (policy) | 200 paginated + `meta.summary` |
| POST | `transactions` | `transactions:write` | `transactions.create` (policy) | 201 (+ `meta.installments`) |
| GET | `transactions/{transaction}` | `transactions:read` | `transactions.view` (policy) | 200 |
| PUT, PATCH | `transactions/{transaction}` | `transactions:write` | `transactions.update` (policy) | 200 |
| DELETE | `transactions/{transaction}` | `transactions:write` | `transactions.delete` (policy) | 204 |
| PATCH | `transactions/{transaction}/pay` | `transactions:write` | `transactions.update` (policy `pay`) | 200 |
| PATCH | `transactions/{transaction}/unpay` | `transactions:write` | `transactions.update` (policy `unpay`) | 200 |
| GET | `transactions/{transaction}/receipt` | `transactions:read` | `transactions.view` (policy `receipt`) | 200 file |
| GET | `goals` | `goals:read` | `goals.view` (policy) | 200 paginated |
| POST | `goals` | `goals:write` | `goals.create` (policy) | 201 |
| GET | `goals/{goal}` | `goals:read` | `goals.view` (policy) | 200 |
| PUT, PATCH | `goals/{goal}` | `goals:write` | `goals.update` (policy) | 200 |
| DELETE | `goals/{goal}` | `goals:write` | `goals.delete` (policy) | 204 |
| GET | `budgets` | `budgets:read` | `budgets.view` (policy) | 200 `data` + `meta {year, month, overview}` |
| POST | `budgets` | `budgets:write` | `budgets.create` (policy) | 201 `{data: {saved_months}, message}` |
| PUT, PATCH | `budgets/{budget}` | `budgets:write` | `budgets.update` (policy) | 200 |
| DELETE | `budgets/{budget}` | `budgets:write` | `budgets.delete` (policy) | 204 |
| GET | `categories` | `categories:read` | `categories.view` (policy) | 200 paginated |
| POST | `categories` | `categories:write` | `categories.create` (policy) | 201 |
| PUT, PATCH | `categories/{category}` | `categories:write` | `categories.update` (policy) | 200 |
| DELETE | `categories/{category}` | `categories:write` | `categories.delete` (policy) | 204 |
| GET | `reports/summary` | `reports:read` | `reports.view` (route middleware) | 200 |

The exact permission names are the ones in `RolesAndPermissionsSeeder::PERMISSIONS`, checked by `App\Policies\OwnedResourcePolicy` and its subclasses. `tests/Feature/Api/ApiRouteRegistryTest.php` freezes the route list (name, method, URI, ability); update it in the same change when an endpoint is added on purpose.

Resources (`app/Http/Resources/V1/*Resource.php`) use explicit allowlists. They never output `user_id`, `deleted_at`, `receipt_path`, media rows or paths, Stripe ids, or the `company_document` of a wallet the caller does not own. Amounts are strings with 2 decimals, dates `Y-m-d`, datetimes ISO-8601.

Wallet update (`PUT`/`PATCH wallets/{wallet}`, and the web `PUT wallets.update`) uses `UpdateWalletRequest` and accepts `company_name` (max 255), `company_document` (max 20), `credit_limit` (numeric ≥ 0), `closing_day` and `due_day` (integer 1 to 31) with the same rules as the store request, plus the other wallet fields. Both verbs accept partial bodies (OpenAPI schema `WalletUpdateInput`, no required fields). Omitted fields keep their current value. Sending `null` clears `company_name`, `company_document`, `credit_limit`, `closing_day` or `due_day` (`WalletDTO::forUpdate()`, list `WalletDTO::CLEARABLE_ON_UPDATE`); on the web form a blank input does the same. These five fields are nullable in the store rules on every wallet type, so clearing them on a credit card is allowed: the card simply has no invoice cycle until both days are set again (`Wallet::hasInvoiceCycle()` is false and the invoice page redirects to the edit form). Invalid values still fail with the usual 422 / pt-BR session errors. For the other fields `null` keeps the current value. `is_shared` is not accepted on update. The web edit form shows the business and credit-card fields, so the invoice redirect ("Informe o dia de fechamento...") leads to a form where they can be filled in.

Deferred to the web app in v1: wallet sharing management, invoices, trash/restore, AI insights and file exports.

## Error envelope

Every `api/*` error has the same JSON body (`App\Exceptions\ApiExceptionRenderer`, wired in `bootstrap/app.php`; `ForceJsonResponse` forces `Accept: application/json`):

```json
{ "message": "Os dados enviados são inválidos.", "code": "validation_failed", "errors": { "name": ["O nome da carteira é obrigatório."] } }
```

`errors` appears only on 422; `abilities` only on `missing_ability`.

| Status | `code` | When |
|---|---|---|
| 401 | `unauthenticated` | No token, malformed, revoked or expired token, or a session cookie without a token |
| 403 | `missing_ability` | Token lacks the route ability |
| 403 | `plan_upgrade_required` | User has no `api_access` (Free) |
| 403 | `plan_limit_reached` | Plan limit on create (unreachable for Profissional, see above) |
| 403 | `forbidden` | Role lacks the Spatie permission, or a policy denies |
| 403 | `http_error` | E-mail not confirmed (`verified`) |
| 404 | `not_found` | Unknown route, non-UUID id, missing record, or a record of another user (never says which) |
| 405 | `http_error` | Method not allowed |
| 422 | `validation_failed` | Form Request / validation errors (pt-BR) |
| 429 | `too_many_requests` | Rate limit (keeps `Retry-After` and `X-RateLimit-*`) |
| 500 | `server_error` | Unexpected error, no trace. With `APP_ENV=local` and `APP_DEBUG=true` Laravel's debug output is returned instead |

## Pagination

`wallets`, `transactions`, `goals` and `categories` return Laravel's resource-collection envelope: `data`, `links {first, last, prev, next}` and `meta {current_page, from, last_page, links, path, per_page, to, total}`. Query parameters `per_page` (1 to 100, default 20; `config('api.per_page_max')`) and `page` (≥ 1). `per_page=101` is 422. `GET budgets` is not paginated: a month has at most one budget per category, and the response carries `meta.year`, `meta.month` and `meta.overview`.

## Isolation rules

- **401**: no valid token.
- **403**: the token lacks the ability, the plan lacks `api_access`, or the role lacks the permission.
- **404**: the record does not exist or belongs to another user. Lookups go through the `BelongsToUser` global scope, then the policy (`denyAsNotFound()` for foreign records). Admins do not bypass ownership.
- Shared wallets: an accepted member can `GET wallets/{wallet}` (`is_owner=false`, no `company_document`), but cannot update or delete it.
- `wallet_id`/`category_id` of another user in a transaction body is 422 (`ownedWalletRule`, `ownedCategoryRule`).

`tests/Feature/Api/ApiEndpointMatrixTest.php` runs 401, `missing_ability`, `forbidden`, `plan_upgrade_required`, 429 and foreign-record 404 against every `api.v1.*` route.

## Receipts

Upload with `multipart/form-data` on `POST transactions` or on update (`receipt`, JPG/PNG/PDF up to 5 MB; `remove_receipt=true` removes it). The resource only exposes `has_receipt` and `receipt_url`, which points to `GET /api/v1/transactions/{id}/receipt`. That download needs the same bearer token and the `transactions:read` ability; another user's receipt is 404. There are no public receipt URLs and no storage paths in any JSON.

## CORS

`config/cors.php`: `paths => ['api/*']`, origins from `CORS_ALLOWED_ORIGINS` (comma-separated; empty means `APP_URL` only), methods `GET, POST, PUT, PATCH, DELETE, OPTIONS`, headers `Authorization, Content-Type, Accept, X-Requested-With`, `supports_credentials => false`, `max_age => 600`. A foreign origin is never echoed in `Access-Control-Allow-Origin`.

## Versioning policy

- `/api/v1` is frozen for breaking changes: no field removals, renames, type changes or stricter validation of existing fields.
- Additive changes are allowed in v1: new endpoints, new optional parameters, new response fields. Clients must ignore unknown fields.
- A breaking change ships as `/api/v2`. v1 stays available for at least 6 months after v2 is released.
- Deprecated endpoints announce it with the `Deprecation` and `Sunset` response headers before removal.

## Swagger / OpenAPI

- UI: `GET /api/documentation` (route `l5-swagger.default.api`). JSON spec: `GET /docs` (route `l5-swagger.default.docs`).
- Access (`App\Http\Middleware\EnsureApiDocsAccess`, with the `web` group): open in `APP_ENV=local` (`config('api.docs_public_in_local')`); elsewhere only a logged-in user with role `admin`. Guests get 404, other users 403. The Swagger UI assets route (`/docs/asset/{asset}`) stays public; it only serves the static swagger-ui files.
- The spec is built from PHP 8 attributes (`OpenApi\Attributes as OA`), scanned in `app/Http/Controllers/Api`, `app/Http/Resources/V1` and `app/OpenApi` (`config/l5-swagger.php`):
  - `app/OpenApi/OpenApiSpec.php`: info, server, `bearerAuth` security scheme, tags, reusable responses `Unauthorized`, `Forbidden`, `NotFound`, `ValidationError`, `TooManyRequests` (with examples), parameters `PerPage`, `Page`.
  - `app/OpenApi/CommonSchemas.php`: `ErrorEnvelope`, `PaginationLinks`, `PaginationMeta`. They live in their own class because swagger-php nests class-level `Schema` attributes into sibling `Parameter` attributes.
  - `app/OpenApi/RequestSchemas.php`: request bodies (`WalletInput`, `WalletUpdateInput`, `TransactionInput`, `TransactionMultipartInput`, `GoalInput`, `CategoryInput`, `BudgetInput`, `BudgetUpdateInput`). The Form Requests stay the source of truth for validation.
  - `#[OA\Schema]` on each `app/Http/Resources/V1/*Resource.php`, and `#[OA\Get|Post|Put|Patch|Delete]` on each API controller method. PUT and PATCH are documented as separate operations. Each operation states its ability in the description and references the 401/403 (and 404/422/429 where they apply) responses.
  - swagger-php copies the class docblock into the first property of a class-level schema when that property has no `description`. Give the first property an explicit description when you add a schema.
- Regenerate after any change to those attributes, and on every deploy:

  ```bash
  php artisan l5-swagger:generate
  ```

  The file goes to `storage/api-docs/api-docs.json`, which is git-ignored (`storage/api-docs/.gitignore`). `L5_SWAGGER_GENERATE_ALWAYS=false` (in `.env.example`) keeps the spec from being rebuilt on every request; set it to `true` only on a local machine while editing attributes.
- `tests/Feature/Api/OpenApiSpecTest.php` generates the spec and fails when an `api.v1.*` route (method + URI) is missing, lacks `bearerAuth` security or the 401/403 responses, or when the spec documents an operation that is not a registered route. `tests/Feature/Api/ApiDocsAccessTest.php` covers the access rules.

## Scout search

- Driver: `SCOUT_DRIVER=database` (`config/scout.php`, `.env.example`, `phpunit.xml`). It runs `LIKE` queries on the application database; no external service. `collection` also works.
- Only `App\Models\Transaction` is searchable. `toSearchableArray()` lists `description`, `notes`, `categories.name` and `wallets.name`; `newScoutQuery()` left-joins `categories` and `wallets` on the transaction's own foreign keys and starts from `newQuery()`, so the `user` global scope and SoftDeletes still apply.
- `TransactionRepository::filteredQuery()` calls `applySearch()`, which builds `Transaction::search($term)` (selecting only `transactions.id`) and filters with `whereIn('transactions.id', <subquery>)`. The term is escaped first (`\`, `%`, `_`), because Scout's database engine does not escape `LIKE` wildcards. The web list, the cached summary and the API `GET transactions?search=` all use this query.
- Subquery engine: `App\Support\Scout\SubqueryDatabaseEngine` extends Scout's `DatabaseEngine` and only adds `searchQuery(Builder)`, which returns the engine's own search query (`buildSearchQuery()`: user scope, SoftDeletes, `LIKE` constraints, no `ORDER BY`, no limit). `AppServiceProvider::boot()` registers it as the `database` driver (`EngineManager::extend('database', ...)`). The match therefore runs as `id in (select transactions.id ...)` inside the list and summary queries: no id list is loaded into PHP and the number of bindings does not grow with the number of matches (`TransactionSearchTest` checks 1 500 matches with ≤ 50 bindings per query).
- Fallback: with any other Scout driver (`collection`, Meilisearch, Algolia...) the engine is not a `SubqueryDatabaseEngine`, so `applySearch()` keeps the previous behaviour, `whereIn('transactions.id', $scout->keys()->all())`. That path loads the matching ids into PHP and should only be used with drivers that cap results.
- `Goal` and `Category` keep their plain filters.
- `php artisan scout:import "App\Models\Transaction"` and `php artisan scout:flush "App\Models\Transaction"` are no-ops with the database engine. Run `scout:import` only after switching to Meilisearch, Algolia or Typesense (and set the engine's env keys).

Tests: `tests/Feature/TransactionSearchTest.php`.

## Framework-table exceptions

`personal_access_tokens` keeps Sanctum's bigint `id`, because the plain token format is `{id}|{secret}`. It has no SoftDeletes (revocation deletes the row). `tokenable` uses `uuidMorphs`, since users have UUID keys. This is the same kind of exception as the Spatie permission, activity log and media tables.

## Configuration keys

| Env key | Default | Purpose |
|---|---|---|
| `SANCTUM_TOKEN_EXPIRATION` | `129600` | Token lifetime in minutes (90 days) |
| `API_RATE_LIMIT_PER_MINUTE` | `60` | Requests per minute per user |
| `CORS_ALLOWED_ORIGINS` | empty (`APP_URL`) | Browser origins allowed on `/api/*` |
| `SCOUT_DRIVER` | `database` | Scout engine |
| `L5_SWAGGER_GENERATE_ALWAYS` | `false` | Rebuild the spec on every docs request |

`config/api.php` also holds `per_page_max` (100) and `docs_public_in_local` (true).

## Deploy checklist

1. `composer install --no-dev --optimize-autoloader`
2. `php artisan migrate` (creates `personal_access_tokens`; additive)
3. `php artisan l5-swagger:generate`
4. `php artisan config:cache` and `php artisan route:cache`
5. Keep the scheduler cron line running (it prunes expired tokens).

## Files added

- Routing and HTTP: `routes/api.php`, `app/Http/Middleware/{ForceJsonResponse,EnsureApiAccess,EnsureApiDocsAccess}.php`, `app/Exceptions/{ApiExceptionRenderer,PlanLimitException}.php`, `app/Http/Controllers/Api/V1/{Auth,Subscription,Wallet,Transaction,Goal,Budget,Category,Report}Controller.php`, `app/Http/Requests/Api/V1/ListRequest.php`, `app/Http/Requests/TransactionIndexRequest.php`, `app/Http/Requests/StoreApiTokenRequest.php`.
- Resources: `app/Http/Resources/V1/{User,Subscription,Wallet,Transaction,Goal,Budget,Category}Resource.php`.
- Tokens: `app/Enums/ApiTokenAbility.php`, `app/DTOs/ApiTokenData.php`, `app/Interfaces/{Repositories/ApiTokenRepositoryInterface,Services/ApiTokenServiceInterface}.php`, `app/Repositories/ApiTokenRepository.php`, `app/Services/ApiTokenService.php`, `app/Http/Controllers/ApiTokenController.php`, `resources/views/settings/tokens.blade.php`.
- OpenAPI: `app/OpenApi/{OpenApiSpec,CommonSchemas,RequestSchemas}.php`, `storage/api-docs/.gitignore`.
- Config: `config/{api,sanctum,cors,scout,l5-swagger}.php`; migration `database/migrations/2026_10_30_214820_create_personal_access_tokens_table.php`.
- Tests: `tests/Feature/Api/*` (foundation, endpoint matrix, route registry, one file per resource, OpenAPI spec, docs access), `tests/Feature/ApiTokenUiTest.php`, `tests/Feature/TransactionSearchTest.php`.
