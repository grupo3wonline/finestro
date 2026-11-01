# Phase 4: Activitylog, Media Library, Laravel Excel, Laravel Data, queued mail, per-user cache, Phase 3 fixes

Phase 4 adds four Spatie/Maatwebsite packages and two framework features to Finestro, and closes the four Phase 3 review findings. Model changes and auth events go to `activity_log`, shown on a per-user "Atividade da conta" page. Receipts move to a single-file `receipt` media collection on the private disk, and an idempotent import command copies legacy files. Report building moves into `ReportService`, with CSV/XLSX produced by one `TransactionsExport` class. The 7 DTOs become Data classes. Report and invitation mail plus the Breeze notifications are queued. Dashboard and report summaries are cached under per-user version keys. Verification recorded in FEAT-006: `composer quality` exit 0, 397 tests / 1355 assertions on both the normal and the `--order-by=random` run (baseline 330 / 1046), PHPStan baseline still 3 entries, `route:list`/`view:cache`/`npm run build` OK, `migrate --pretend` showed only `create table activity_log` and `create table media` before migrating.

Watch for: the Stripe `resource_missing` shortcut also fires when the configured key belongs to a different Stripe mode or account (likely), which would delete the account while a real subscription keeps billing. Receipt file writes and deletes are not rolled back with the DB transaction (confirmed, low impact). Neither of these blocks the phase.

**Verdict**: APPROVED

## High-level view

Per-user isolation holds on every surface the task named. The activity page has no id parameter. Its query returns rows where the user is the causer, or where the user is the subject and the causer is null (failed logins). Receipt download, replace and remove go through the scoped `getTransactionById()` before any policy check, so another user gets 404. The test also proves nothing is written under the intruder's folder. Exports reuse the scoped `getTransactionsByDateRange()` with the same `permission:reports.export` middleware and `reports_export` plan check. Cache keys always start with `finestro:user:{userId}:v{version}`, and they are skipped entirely when there is no authenticated user.

The activity log uses per-model allowlists (`LogsOwnedActivity::activityLogAttributes()`) with `logOnlyDirty()` and `dontLogEmptyChanges()`. `User` and `AiCredential` are not logged, and neither are `balance`, `company_document`, `notes`, `receipt_path` or Stripe ids. Security rows store only the event, the IP and role names. Without an authenticated user the owner becomes the causer, tagged `via=system`. Retention is `ACTIVITYLOG_RETENTION_DAYS` (365), with `activitylog:clean --force` scheduled daily. The two package migrations are pure `CREATE TABLE`s with UUID morphs, and `transactions.receipt_path` is kept and only read as a fallback.

The CSV keeps the 10 pt-BR headers, the `;` delimiter and the UTF-8 BOM. PhpSpreadsheet now quotes every field, which parses to the same values. `StringValueBinder` keeps amounts as `1.234,56` text in XLSX and stops `=` descriptions from being evaluated during the write.

Both mailables and the two notification subclasses implement `ShouldQueue` with `tries = 3` and `backoff = [60, 300]`. The report mail now carries the scalar `summaryFor()` array plus the CSV string. The `jobs`/`failed_jobs` tables already existed, so no queue migration was needed. An opt-in `QUEUE_RUN_VIA_SCHEDULER` entry covers shared hosting from the single cron line.

All four Part B items are done. Account deletion maps a Stripe `resource_missing`/404 to `StripeResourceMissingException`, marks the subscription canceled locally and continues, while any other failure still blocks. The Phase 2 doc now describes the real retry rule. A Pest test pins the soft-deleted-owner skip, and the Phase 3 records were corrected. The Rector/PHP 8.4 rationale was fixed in the doc and in the `rector.php` comment. `docs/infrastructure/phase-4-packages.md` covers every item the task listed.

<details>
<summary>Issues (4)</summary>

1. **Stripe mode/account mismatch treated as "already canceled"** — `StripeGateway::isResourceMissing()` matches any `resource_missing`/404, which Stripe also returns when a test key looks up a live object (or a key from another account). Log this branch at error level and treat messages like "a similar object exists in live mode" as a real failure, or verify the customer exists before marking the subscription canceled locally.
2. **Receipt file operations outside the DB rollback** — `ReceiptService::remove()`/`attach()` delete or write files inside `DB::transaction()`. A later failure rolls back the media rows but not the files, which leaves orphan files or a media row whose file is gone (the download then returns 404). If this matters, defer `remove()` with `DB::afterCommit()`, or attach last and clean up on failure.
3. **Raw UUIDs on the activity page** — Transaction `wallet_id`/`category_id` and Budget `category_id` changes are shown as UUIDs under "Carteira"/"Categoria". Resolve names in `AccountActivityService` or leave those fields out of the page rendering.
4. **XLSX path not covered by the gating tests** — the Free-plan redirect and the missing-`reports.export` 403 tests use the default CSV format only. Add a `format=xlsx` case to each (cheap, same route).

</details>

<details>
<summary>Details</summary>

### Account deletion and Stripe `resource_missing`

```
AccountDeletionService::delete
  └─ SubscriptionService::cancelImmediately
       └─ StripeGateway::cancelImmediately
            InvalidRequestException && (code resource_missing || 404) → StripeResourceMissingException
  catch StripeResourceMissingException → Log::warning, markCanceledLocally, continue
  catch Throwable                      → Log::error, AccountDeletionException (blocks)
  security->log('account_deleted') → $user->delete()
```

The risk is what "missing" means to Stripe. A test-mode key asking for a live-mode subscription, or a key from a different account, gets `resource_missing` ("No such subscription ... a similar object exists in live mode"). With the exposed key pending rotation, a misconfigured key would let every paying user delete their account while the real subscription keeps charging. This failure mode is silent, and only a `warning` log line is left behind. Likely, not blocking, since it requires misconfiguration.

### Receipt storage and transactional boundaries

`TransactionService::updateTransaction()` runs `receipts->remove()` (which deletes the media file from disk) and then `receipts->attach()` inside `DB::transaction()`. If `attach()` throws, or `applyToWallet()`/`record()` fail afterwards, the media row comes back on rollback but its file is already gone. `hasReceipt()` then reports true and the download returns 404 (`ReceiptService::response()` deliberately does not fall back to `receipt_path` when a media row exists). The mirror case on create leaves an orphan file under `receipts/{user}/media/{uuid}/` with no row. Confirmed by reading the code. The triggering conditions are rare, because the Form Request `mimes` rule and the media-library mime check cover the same set.

The import command (`receipts:import-media`) is unaffected: it never deletes sources (`preservingOriginal()`) or writes `receipt_path`, and its test proves dry-run, a single import, a second run importing 0, and that the source file survives.

### Activity page rendering

`AccountActivityService::changes()` prints `old → new` straight from `attribute_changes`. For foreign keys that means a UUID under the pt-BR label "Carteira" or "Categoria", which means nothing to the user. This only affects the page; no data from another user is exposed, because the UUID belongs to the user's own wallet or category.

### Test coverage

The tests cover everything in the DONE list. Activity tests check causer, allowlist, no keys/Stripe ids/passwords, every auth event, `via=system`, isolation including `?user_id=&causer_id=`, and the guest redirect. Receipt tests cover upload, installments, replace, remove, legacy removal, 404 on download/update/remove, content sniffing, the legacy fallback and the import. Export tests check BOM, `;`, headers, the foreign wallet id, the type filter, XLSX parsed with PhpSpreadsheet, `=` as text, Free redirect, 403 and an invalid format. Cache tests prove isolation, a cache hit after a raw insert, invalidation by each model, the report summary, distinct keys and the null-user bypass. Queue tests assert queued classes, the scalar payload, tries/backoff and the two notifications.

Not tested: XLSX under the Free/403 gating; a `role_detached` event; the receipt rollback scenario above.

</details>

<details>
<summary>File map</summary>

Phase 4 files (most are untracked, so `git diff` shows only the modified ones; read them in place):

- `app/Exceptions/StripeResourceMissingException.php`, `app/Services/StripeGateway.php`, `app/Interfaces/Services/StripeGatewayInterface.php`, `app/Services/SubscriptionService.php` (+interface), `app/Services/AccountDeletionService.php`: Part B-1 resource_missing handling and `account_deleted` log.
- `docs/billing/phase-2-stripe.md`, `docs/quality/phase-3-tooling.md`, `rector.php` (comment), `.agents/tasks/phase-3-quality/.../FEAT-002.json`, `FEAT-003.json`: Part B-2/3/4 corrections.
- `app/DTOs/*.php` (7 converted, plus new `ActivityEntryData`, `ReportFilterData`): Data classes, `withOverrides()` rename, `Hidden` AI keys, receipt fields on `TransactionDTO`.
- `config/activitylog.php`, `database/migrations/2026_10_30_184157_create_activity_log_table.php`, `config/permission.php`: activity log setup, UUID morphs, `events_enabled`.
- `app/Traits/LogsOwnedActivity.php`, `app/Listeners/LogAuthenticationActivity.php`, `app/Services/SecurityActivityLogger.php`, `app/Repositories/ActivityRepository.php`, `app/Services/AccountActivityService.php` (+interfaces): logging and the scoped page query.
- `app/Http/Controllers/SettingController.php`, `app/Http/Controllers/Auth/PasswordController.php`, `routes/web.php`, `resources/views/settings/{activity,index}.blade.php`: activity page, `password_changed`.
- `config/media-library.php`, `database/migrations/2026_10_30_185927_create_media_table.php`, `app/Support/MediaLibrary/ReceiptPathGenerator.php`, `app/Services/ReceiptService.php` (+interface), `app/Console/Commands/ImportReceiptsToMedia.php`: receipts on media and the import.
- `app/Models/{Transaction,Wallet,Goal,Budget,Category,Subscription}.php`: activity, media and cache traits.
- `app/Services/TransactionService.php`, `app/Http/Controllers/TransactionController.php`, `app/Http/Requests/Concerns/TransactionRules.php`, `app/Repositories/TransactionRepository.php`, `resources/views/transactions/{show,index,partials/form}.blade.php`: receipt flow, `remove_receipt`, cached `getSummary()`.
- `app/Exports/TransactionsExport.php`, `app/Services/ReportService.php` (+interface), `app/Http/Requests/ReportRequest.php`, `app/Http/Controllers/ReportController.php`, `resources/views/reports/index.blade.php`: Excel exports, thin controller, XLSX button.
- `app/Services/UserCache.php` (+interface), `app/Traits/FlushesUserCache.php`, `config/cache.php`: per-user cache.
- `app/Mail/{FinancialReportMail,WalletInvitationMail}.php`, `app/Notifications/{QueuedVerifyEmail,QueuedResetPassword}.php`, `app/Models/User.php`, `app/Services/WalletSharingService.php`, `config/queue.php`, `routes/console.php`, `.env.example`: queueing and the scheduler.
- `docs/infrastructure/phase-4-packages.md`: Phase 4 documentation.
- Tests: `tests/Feature/{ActivityLogTest,ReceiptMediaTest,ReportExportTest,UserCacheTest,QueuedMailTest,RecurringMissingOwnerTest,AccountDeletionTest}.php`, `tests/Feature/Data/DtoDataConversionTest.php`, `tests/Unit/StripeGatewayResourceMissingTest.php`, plus updated `PasswordResetTest`, `ReportAccessTest`, `BudgetInvoiceRecurringTest`, `TransactionLifecycleTest`.

Full diff: `git -C <root> diff` for tracked files, and read the untracked files listed above directly.

</details>
