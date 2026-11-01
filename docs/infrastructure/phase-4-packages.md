# Phase 4: infrastructure packages

Phase 4 adds the following packages and framework features to Finestro. Each one has its own section below.

| Package / feature | Installed version | Constraint | Used for |
|---|---|---|---|
| `spatie/laravel-data` | 4.23.1 | `^4.23` | Base class of the DTOs in `app/DTOs` and `ReportFilterData` |
| `spatie/laravel-activitylog` | 5.1.3 | `^5.1` | Account activity log (model changes + auth/security events), page **Configurações → Atividade da conta** |
| `spatie/laravel-medialibrary` | 11.23.11 | `^11.23` | Transaction receipts on the `private` disk |
| `maatwebsite/excel` | 4.0.6 | `^4.0` | CSV/XLSX report exports and the CSV attached to the report e-mail (pulls `phpoffice/phpspreadsheet` 5.10) |
| Laravel Cache | framework | | Per-user cache with version-key invalidation |
| Laravel Queue | framework | | Queued mail and auth notifications |

### Config keys and environment variables

| Env var (`.env.example`) | Config key | Default | Effect |
|---|---|---|---|
| `ACTIVITYLOG_RETENTION_DAYS` | `activitylog.clean_after_days` | 365 | Days activity rows are kept by `activitylog:clean` |
| `CACHE_USER_TTL` | `cache.user_ttl` | 300 | Seconds a per-user cache entry lives |
| `CACHE_STORE` | `cache.default` | `database` | Store used by the per-user cache |
| `QUEUE_CONNECTION` | `queue.default` | `database` | Queue backend (`jobs` / `failed_jobs` tables) |
| `QUEUE_RUN_VIA_SCHEDULER` | `queue.run_via_scheduler` | `false` | Shared-hosting fallback: run the worker from `schedule:run` |
| `QUEUE_CONVERSIONS_BY_DEFAULT` | `media-library.queue_conversions_by_default` | `true` | Media conversions on the queue (none registered today) |
| (none) | `media-library.disk_name` | `private` | Receipt disk (`storage/app/private`, `config/filesystems.php`) |

## spatie/laravel-data

Version: `^4.23` (4.23.1 installed). The package config is not published; the defaults are used.

- The seven classes in `app/DTOs` (`AiCredentialDTO`, `BudgetDTO`, `CategoryDTO`, `GoalDTO`, `SubscriptionDTO`, `TransactionDTO`, `WalletDTO`) extend `Spatie\LaravelData\Data`.
- `fromArray()` and `toArray()` remain the API used by services and repositories. `toArray()` is overridden in every DTO and returns the persistence array (snake_case columns, same null handling as before), so it does not go through Data's transformer. Data's own output is available through `transform()` / `toJson()` (camelCase property names).
- `TransactionDTO::with(array)` was renamed to `withOverrides(array $overrides): self`, because `Data::with()` already exists (appended properties, returns an array). Its only caller is `TransactionService::createInstallments()`.
- `TransactionDTO` keeps its `private array $provided`; private properties are not part of Data output.
- `AiCredentialDTO` marks `geminiApiKey`, `groqApiKey` and `openrouterApiKey` with `#[Spatie\LaravelData\Attributes\Hidden]`, so `transform()`, `toJson()` and JSON responses never contain the API keys. `toArray()` still includes them for persistence.
- Form Requests remain the validation layer. `Data::from()` and Data validation are not used in place of them.
- New DTOs added during Phase 4 (for example `ReportFilterData`) also extend `Data`.
- No PHPStan baseline entry was needed for this package.

Tests: `tests/Feature/Data/DtoDataConversionTest.php`.

## Activity log (spatie/laravel-activitylog)

Version: `^5.1` (5.1.3 installed). Published files: `config/activitylog.php` and `database/migrations/2026_10_30_184157_create_activity_log_table.php`.

The activity log records who did what in an account. It complements `App\Models\TransactionHistory` and does not replace it: `TransactionHistory` keeps the detailed per-transaction timeline (with the edit reason) shown on the transaction page, while the activity log is the account-wide audit trail shown on **Configurações → Atividade da conta** (`GET /configuracoes/atividade`, route `settings.activity`).

### Model changes (log name `model`)

The trait `App\Traits\LogsOwnedActivity` wraps the package `LogsActivity` trait. Each model declares an allowlist in `activityLogAttributes()`; only those attributes are written, only when they changed (`logOnlyDirty()`), and an update that touches no allowlisted attribute writes nothing (`dontLogEmptyChanges()`). Events: `created`, `updated`, `deleted`, `restored`.

| Model | Logged attributes | Never logged |
|---|---|---|
| `Wallet` | name, type, ownership, company_name, credit_limit, closing_day, due_day, currency, color, icon, description, is_active, is_shared | `balance` (balance moves are already covered by the transaction rows), `company_document` |
| `Transaction` | wallet_id, category_id, type, status, amount, description, date, due_date, paid_at, payment_method, is_recurring, recurring_frequency, installment_number, installment_total | `notes`, `receipt_path` |
| `Goal` | name, description, target_amount, current_amount, deadline, status | |
| `Budget` | category_id, year, month, amount, alert_threshold | `notes` |
| `Category` | name, type, icon, color | |
| `Subscription` | plan, status, cancel_at_period_end, current_period_end, canceled_at (plan changes) | Stripe ids |

`User` and `AiCredential` (API keys) have no model logging. `config('activitylog.default_except_attributes')` also excludes `password`, `remember_token` and the timestamps on every model.

### Auth and security events (log name `security`)

`App\Listeners\LogAuthenticationActivity` (an event subscriber registered in `AppServiceProvider::boot()`) and `App\Services\SecurityActivityLogger` (`SecurityActivityLoggerInterface`) record:

| Event | Source |
|---|---|
| `login`, `logout`, `login_failed`, `password_reset`, `registered` | Laravel auth events (`Login`, `Logout`, `Failed`, `PasswordReset`, `Registered`) |
| `role_attached`, `role_detached` | Spatie permission events (`config/permission.php` `events_enabled => true`); only the role names are stored |
| `password_changed` | `SettingController::updatePassword()` and Breeze `Auth\PasswordController::update()` |
| `account_deleted` | `AccountDeletionService::delete()`, right before the user is soft-deleted |
| `api_token_created`, `api_token_revoked` | `ApiTokenService` (Phase 5, **Configurações → Tokens de API**); shown as "Criou um token de API" / "Revogou um token de API" with a `Token` row (the token name) |

Security rows store the event name, the request IP address (`properties.ip`) and, for role events, the role names. Token events store `token_name` and `abilities`, never the plain token or its hash. Failed-login credentials, passwords and reset tokens are never read or stored.

### Wallet and category names (Phase 5)

Transaction rows log `wallet_id` and `category_id`. The page shows names instead of UUIDs: `AccountActivityService::paginateForUser()` collects the ids of the current page and resolves them with `WalletRepositoryInterface::namesForUser()` and `CategoryRepositoryInterface::namesForUser()` (2 queries per page, owner-scoped, soft-deleted rows included). The value shown is `Nome`, `Nome (excluída)` for a soft-deleted record, or `(excluída)` when the id is not one of the user's records. The last case also covers an id of another user's wallet, whose name is never shown.

### Causer rules

- Model activity done by a logged-in user: causer = that user.
- Model activity without an authenticated user (scheduler, queue, Stripe webhook): `beforeActivityLogged()` sets the causer to the model owner (`user_id`) and adds `properties.via = "system"`. The page shows a "via sistema" badge.
- Security events: causer = the authenticated user, falling back to the subject user (for example `registered` and `role_attached` during sign-up).
- `login_failed`: causer is always null (the actor is unknown); the subject is the targeted user when the login matched an account.

### Page isolation

`ActivityRepository::paginateForUser()` returns rows where the causer is the user, or the subject is the user and there is no causer (failed logins against the account). The route has no id parameter and query parameters such as `?user_id=` are ignored, so another user's log cannot be addressed.

### Retention

`clean_after_days` reads `ACTIVITYLOG_RETENTION_DAYS` (default 365, listed in `.env.example`). `routes/console.php` schedules `activitylog:clean --force` daily; `--force` is needed because the command asks for confirmation in production. The page tells the user how many days entries are kept.

Account deletion soft-deletes the user and does not touch `activity_log`: the account's rows (including the `account_deleted` row and the stored IP addresses) stay until `activitylog:clean` removes them after the retention window.

### Schema deviation

The package stub uses bigint morphs. Users and every domain model use UUID keys, so the published migration uses `nullableUuidMorphs('subject')` and `nullableUuidMorphs('causer')` and adds an index on `created_at`. The table keeps its bigint `id` and has no SoftDeletes. It is an append-only audit log pruned by `activitylog:clean`, the same exception as the Spatie permission tables.

No PHPStan baseline entry was needed for this package.

Tests: `tests/Feature/ActivityLogTest.php`.

## Receipts (spatie/laravel-medialibrary)

Version 11.23.11 (`^11.23`). Transaction receipts are media items; `ReceiptServiceInterface` / `ReceiptService` owns attach, remove and download.

### Collection rules

- `Transaction` implements `HasMedia` and registers the `receipt` collection (`Transaction::RECEIPT_COLLECTION`): `singleFile()`, disk `private`, `acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf'])`.
- The Form Request still validates first (`mimes:jpg,jpeg,png,pdf`, `max:5120`). The media library checks again with the sniffed content type and `max_file_size`; its `FileUnacceptableForCollection` / `FileIsTooBig` become a `ValidationException` on `receipt` with the same pt-BR messages, which also rolls back the surrounding DB transaction.
- The stored file name is a random UUID plus the lower-cased extension; the client file name never reaches the disk.
- `Transaction::hasReceipt()` is true when there is receipt media or a legacy `receipt_path`. Views use it for the paperclip indicator, the "Ver comprovante" link and the edit form block. The list and `findById()` eager-load `media`.

### Disk and path layout

`App\Support\MediaLibrary\ReceiptPathGenerator` (registered in `custom_path_generators` for `Transaction`) stores files at `receipts/{owner user_id}/media/{media uuid}/{file}` on the `private` disk (`storage/app/private`, never public). Conversions and responsive images would go to `conversions/` and `responsive/` under the same folder; none are registered. The owner id is read without global scopes, so web requests, console commands and soft-deleted rows resolve the same path (`unknown` if the row is gone).

### Upload, replace, remove, download

- Upload on create and on edit: the validated `UploadedFile` travels in `TransactionDTO::$receipt` (not persisted, not carried by `withOverrides()`), and `TransactionService` attaches it inside its DB transaction. A new upload replaces the previous media (single file). New uploads never write `receipt_path`.
- Installment purchases: the receipt is attached to installment 1 only.
- Removal: the edit form checkbox `remove_receipt` (`TransactionDTO::$removeReceipt`). The service clears the media collection (the media file is deleted) and sets `receipt_path` to null when it was filled. The legacy file itself is never deleted. If a new file is sent in the same request, it becomes the receipt.
- The history diff keeps the `receipt_path` key ("Comprovante": `anexado` / empty) computed from `hasReceipt()`.
- Download (`transactions.receipt`): scoped lookup (another user's id is 404), then `authorize('receipt')`; `ReceiptService::response()` serves the media file, otherwise the legacy `receipt_path` file, otherwise 404. Same file name `comprovante-Y-m-d.ext` and headers (`Content-Disposition: inline`, `X-Content-Type-Options: nosniff`) as before. Changes use `authorize('update')` after the same scoped lookup, so nothing is stored for a foreign id.
- Soft delete keeps the media; the package deletes media rows and files only on force delete.

### Files follow commit and rollback (Phase 5)

Receipt files now match the outcome of the surrounding DB transaction:

- `ReceiptService::remove()` deletes the media rows with `Media::withoutEvents()` (so Spatie's observer does not delete the file right away) and deletes the files with `DB::afterCommit()`. If the transaction rolls back, the row and the old file are both kept.
- `ReceiptService::attach()` calls `remove()` first, so the `singleFile()` auto-clear never deletes the old file mid-transaction, and returns the new `Media`.
- `TransactionService::createTransaction()` and `updateTransaction()` catch any failure, call `ReceiptService::discard()` on the media written in that call (deletes the new file) and rethrow. A failed create leaves no transaction, media row or file; a failed replace keeps the old receipt intact.

Tests: `tests/Feature/ReceiptMediaTest.php` (failure cases forced with `TransactionHistory::creating(fn () => throw ...)`).

### Importing legacy receipts

```bash
php artisan receipts:import-media --dry-run   # report only
php artisan receipts:import-media             # copy files into media
```

The command walks every transaction with a `receipt_path` (no user scope, soft-deleted rows included) in chunks of 100. Rows that already have receipt media are counted as "Já importados"; rows whose file is missing are listed and counted as "Arquivo ausente"; the rest are copied with `addMediaFromDisk(...)->preservingOriginal()`. It never deletes the source file and never changes `receipt_path`, so it is safe to run again (the second run imports 0). A file the media library refuses is counted as "Com erro" and the run continues.

### Config (`config/media-library.php`, published)

`disk_name => 'private'`, `max_file_size => 5 MB`, `queue_conversions_by_default => env('QUEUE_CONVERSIONS_BY_DEFAULT', true)` (no conversions are registered, so this has no effect today), `custom_path_generators => [Transaction::class => ReceiptPathGenerator::class]`. Everything else is the package default.

### Schema deviation

The package stub uses `morphs('model')` (bigint). Every domain model uses UUID keys, so the published migration uses `uuidMorphs('model')` and has a `down()`. The `media` table keeps its bigint `id` and has no SoftDeletes, the same exception as the Spatie permission and activity tables. `transactions.receipt_path` is kept (additive change only).

Tests: `tests/Feature/ReceiptMediaTest.php`, `tests/Feature/TransactionLifecycleTest.php` (receipt test), `tests/Feature/Authorization/TransactionAccessTest.php` (unchanged, legacy fallback).

## Exports (maatwebsite/excel)

Package: `maatwebsite/excel` 4.0.6 (`^4.0`, pulls `phpoffice/phpspreadsheet` 5.10). The config is not published; every setting the export needs is on the export class.

### Export class

`App\Exports\TransactionsExport` (`FromCollection`, `WithHeadings`, `WithMapping`, `WithCustomCsvSettings`, `WithCustomValueBinder`) is the single source of columns for the CSV download, the XLSX download and the CSV attached to the report e-mail. Columns (pt-BR): Data, Vencimento, Descrição, Parcela, Tipo, Situação, Forma de pagamento, Carteira, Categoria, Valor. The mapping is the one the old hand-written CSV used (dates `d/m/Y`, amount `1.234,56`, `-` for a missing wallet/category).

The class extends PhpSpreadsheet's `StringValueBinder`, so every cell is written as text. Amounts keep the pt-BR format in both formats, and a description that starts with `=` is stored as text instead of being evaluated as a formula. CSV has no cell types, so in the CSV writer (download and e-mail attachment) text starting with `=`, `+`, `-`, `@`, tab or CR gets a leading apostrophe (`EscapesFormulaInjection`). The XLSX writer keeps the original text without the apostrophe; `ReportService` passes the writer type to the export (Phase 6.5).

### CSV settings

`getCsvSettings()`: delimiter `;`, enclosure `"`, line ending `\n`, UTF-8 BOM on. PhpSpreadsheet encloses every field in quotes, which the old `fputcsv()` output did only when needed. Both are valid CSV and parse to the same values.

### Formats and route

The format is the `format` query parameter on the existing `reports.export` route: `csv` (default) or `xlsx`. Any other value fails validation in `App\Http\Requests\ReportRequest` ("Formato de exportação inválido."). File names: `finestro_relatorio_{start}_{end}.csv|.xlsx`; content types `text/csv; charset=UTF-8` and `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`. The controller still answers with `response()->streamDownload()` around `Excel::raw()`, so tests can read the body with `streamedContent()`.

Gating is unchanged: route middleware `permission:reports.export` (403 without it) and the plan feature `reports_export` (Free users are redirected to `reports.index` with the existing pt-BR error). The reports page has a new XLSX button next to CSV. PDF stays on `barryvdh/laravel-dompdf`. Since Phase 5, the Free-plan redirect and the missing-permission 403 tests in `ReportExportTest` run as `csv`/`xlsx` datasets, so both formats are covered.

### Report service

`App\Services\ReportService` (`ReportServiceInterface`) builds the report for the screen, print, PDF, e-mail and exports; `ReportController` only checks the plan and calls it. Filters arrive through `ReportRequest::filters()` as `App\DTOs\ReportFilterData` (spatie Data; missing dates default to the current month; `wallet_id` or `wallet`). The transaction list and the top 5 expenses are always read live; the other aggregates come from the cached report summary (next section). `summaryFor()` returns the scalar summary the e-mail template uses.

Tests: `tests/Feature/ReportExportTest.php`.

## Cache

`App\Services\UserCache` (`UserCacheInterface`) caches per-user aggregates on the default cache store. The `database` store has no tags, so invalidation uses a version key per user:

- version key: `finestro:user:{userId}:version` (random string, written with `Cache::forever`; missing means `0`)
- entry key: `finestro:user:{userId}:v{version}:{name}:{md5(json_encode(params sorted by key))}`

Every key contains the user id, so one user's entries are never read for another user. `flush($userId)` writes a new version; old entries are never read again and expire after the TTL.

TTL: `config('cache.user_ttl')`, env `CACHE_USER_TTL`, default 300 seconds.

### What is cached

- `TransactionService::getSummary($filters)` as `transactions.summary` (dashboard and transactions index).
- The report summary as `reports.summary` (totals, per category/wallet/day/payment method, previous-period change, fixed vs variable, paid vs pending).

Only plain arrays and scalars are cached, never Eloquent models (`cache.serializable_classes` is `false`, so objects would not unserialize). With no authenticated user (console, webhook, queue) `remember()` runs the callback without caching.

### Invalidation

`App\Traits\FlushesUserCache` on `Transaction`, `Wallet`, `Goal` and `Budget` flushes the owner (`user_id`) on `created`, `updated`, `deleted`, `restored` and `forceDeleted`. `Model::increment()`/`decrement()` fire `updated`, so wallet balance moves invalidate too.

Caveats:

- Query-builder writes (`DB::table(...)`, mass `Model::query()->update()`) fire no model events. Their effect shows up after the TTL.
- The flush runs when the event fires, inside any open DB transaction. A concurrent request that recomputes the summary before that transaction commits can cache the old values until the TTL.
- Shared wallets: a change flushes only the record owner's cache. Other members see it after the TTL.
- Category renames are not an invalidation trigger; report summaries keyed by category name refresh after the TTL.

Tests: `tests/Feature/UserCacheTest.php`.

## Queue

Connection: `database` (`QUEUE_CONNECTION=database`). The `jobs`, `job_batches` and `failed_jobs` tables come from `0001_01_01_000002_create_jobs_table.php`, which was already migrated, so Phase 4 adds no queue migration. Tests run with `QUEUE_CONNECTION=sync` (`phpunit.xml`).

### What is queued

| Class | Sent from | Retries |
|---|---|---|
| `App\Mail\FinancialReportMail` | `ReportController::email()` via `Mail::to($user)->queue()` | `tries = 3`, `backoff = [60, 300]` |
| `App\Mail\WalletInvitationMail` | `WalletSharingService::sendInvitationEmail()` via `Mail::to()->queue()` (still inside its try/catch + `report($e)`) | same |
| `App\Notifications\QueuedVerifyEmail` (extends Breeze `VerifyEmail`) | `User::sendEmailVerificationNotification()` | same |
| `App\Notifications\QueuedResetPassword` (extends Breeze `ResetPassword`) | `User::sendPasswordResetNotification()` | same |

All four implement `ShouldQueue`. A failed attempt is retried after 60 s, then after 300 s; after the third failure the job goes to `failed_jobs`.

The report e-mail payload is the scalar summary from `ReportService::summaryFor()` (plain arrays and numbers, no Eloquent models or collections) plus the CSV string. The `User` model is serialized by id (`SerializesModels`) and reloaded by the worker.

The database queue stores job payloads unencrypted: until a job runs, the `jobs` row holds the report summary and CSV (report e-mail) or the plain reset token (`QueuedResetPassword`; `password_reset_tokens` keeps only its hash). The row is deleted when the job succeeds. A job that fails three times keeps its payload in `failed_jobs` until `queue:retry` or `queue:flush`. `SerializesModels` restores models without global scopes, so a job queued just before the account was soft-deleted is still delivered to that address.

**Mail is not delivered until a worker runs.** Without a worker, the jobs stay in the `jobs` table. The verification and reset links are built by the worker from `APP_URL`, so `APP_URL` must be the public URL of the site.

### Running a worker

- Local (Laragon): `php artisan queue:work --tries=3`, or `composer dev`, which already starts `php artisan queue:listen --tries=1 --timeout=0` next to the server and Vite.
- VPS (Supervisor), `/etc/supervisor/conf.d/finestro-worker.conf`:

  ```ini
  [program:finestro-worker]
  process_name=%(program_name)s_%(process_num)02d
  command=php /path/to/finestro/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
  autostart=true
  autorestart=true
  stopasgroup=true
  killasgroup=true
  user=www-data
  numprocs=1
  redirect_stderr=true
  stdout_logfile=/path/to/finestro/storage/logs/worker.log
  stopwaitsecs=3600
  ```

  Then `supervisorctl reread && supervisorctl update && supervisorctl start finestro-worker:*`. Run `php artisan queue:restart` after every deploy so workers load the new code.
- Shared hosting (no long-running processes): set `QUEUE_RUN_VIA_SCHEDULER=true`. `routes/console.php` then schedules `queue:work --stop-when-empty --max-time=50 --tries=3` every minute with `withoutOverlapping()`, so the single scheduler cron line below also drains the queue. Alternative: a separate cron entry `* * * * * cd /path/to/finestro && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1`.

### Failed jobs

- `php artisan queue:failed` lists them.
- `php artisan queue:retry all` (or `queue:retry <uuid>`) puts them back on the queue.
- `php artisan queue:flush` deletes them all.

Tests: `tests/Feature/QueuedMailTest.php`; `PasswordResetTest`, `ReportAccessTest` and `BudgetInvoiceRecurringTest` assert the queued classes (`QueuedResetPassword`, `Mail::assertQueued`).

## Scheduler

One cron line runs every scheduled task:

```cron
* * * * * cd /path/to/finestro && php artisan schedule:run >> /dev/null 2>&1
```

Entries (`php artisan schedule:list` with `QUEUE_RUN_VIA_SCHEDULER=false`):

```
0 0 * * *  php artisan transactions:process-recurring
0 0 * * *  php artisan activitylog:clean --force
0 0 * * *  php artisan sanctum:prune-expired --hours=24   (Phase 5)
```

With `QUEUE_RUN_VIA_SCHEDULER=true` a fourth entry appears: `* * * * *  php artisan queue:work --stop-when-empty --max-time=50 --tries=3`.

## PHPStan baseline

Phase 4 added no baseline entries. `phpstan-baseline.neon` still holds the 3 `Filterable` entries from earlier phases (`Goal`, `Transaction`, `Wallet`).

## Manual steps for the user

1. `composer install` (installs the four new packages from `composer.lock`).
2. `php artisan migrate` (creates `activity_log` and `media`; additive only).
3. Copy the new keys from `.env.example` into `.env` if you want non-default values: `ACTIVITYLOG_RETENTION_DAYS`, `CACHE_USER_TTL`, `QUEUE_RUN_VIA_SCHEDULER`.
4. Set `APP_URL` to the public URL, so queued verification and reset links point to the right host.
5. `php artisan receipts:import-media --dry-run`, check the counts, then `php artisan receipts:import-media`.
6. Start a queue worker (see "Running a worker") or set `QUEUE_RUN_VIA_SCHEDULER=true` on shared hosting.
7. Add the scheduler cron line.
8. `php artisan optimize:clear` after changing `.env`.

## Deferred

- Laravel Scout, Sanctum/API Resources/Swagger shipped in Phase 5 (`docs/api/phase-5-api.md`). Filament is still pending.
- Legacy receipt files stay in `receipts/{user_id}/` after the import; delete them manually once the import is verified.
- Shared-wallet members' caches are not flushed by another member's change (they refresh after the TTL).
- No media conversions (thumbnails) are registered.
