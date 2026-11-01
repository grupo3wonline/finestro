# Phase 2: Stripe billing

This document describes how Finestro bills the Profissional plan through Stripe, how to configure it, and which rules decide whether a user has Pro access. It covers the work of Phase 2 (plan items 1-16 in `.agents/tasks/phase-2-stripe/plan.md`).

> **Plan model.** Phase 2 ships a two-tier model (Básico/Free and Profissional) that supersedes the earlier three-tier draft (Free/Padrão/Premium) in the product spec. Only these two plans exist in code: `App\Models\User::getPlanLimitsAttribute()` returns Profissional limits when `isProfessional()` is true, and Free limits otherwise. This document is authoritative for billing.

## Quick Start (local)

Get a working local billing setup in order:

1. Add the Stripe test keys to `.env`: `STRIPE_PUBLISHABLE_KEY`, `STRIPE_SECRET_KEY`.
2. `php artisan config:clear`
3. `php artisan stripe:setup` — copy the printed `STRIPE_PROFESSIONAL_PRICE_ID=price_...` into `.env`.
4. `php artisan config:clear` again.
5. In a second terminal, forward webhooks: `stripe listen --forward-to http://<host>/stripe/webhook`. Copy the printed `whsec_...` into `STRIPE_WEBHOOK_SECRET`, then `php artisan config:clear` once more.
6. Visit `/assinatura` to see the Financeiro page and start a checkout.

The rest of this document explains each of these pieces in detail.

## Plans and limits

There are two plans. Limits come from `App\Models\User::getPlanLimitsAttribute()` and are enforced in controllers, policies and services (including the recurring-transactions command). The `plan.limit` middleware is available but not attached to any route.

| Limit | Básico (Free) | Profissional |
| --- | --- | --- |
| Wallets | 1 | Unlimited |
| Transactions per month | 100 | Unlimited |
| Goals | 2 | Unlimited |
| Categories | 5 | Unlimited |
| Budgets per month | 3 | Unlimited |
| Simulators | No | Yes |
| Report export (CSV) | No | Yes |
| AI insights | No | Yes |
| Wallet sharing | No | Yes |
| REST API (`api_access`, Phase 5) | No | Yes |

`api_access` has an explicit arm in `User::hasFeature()` (its `match` default is `true`). `config/stripe.php` lists "Acesso à API REST com tokens pessoais" in the Profissional features and "Acesso à API REST" in the Free limitations. Free users get 403 `plan_upgrade_required` from every `/api/v1` route (`EnsureApiAccess`), which also blocks a downgraded user's old tokens; see `docs/api/phase-5-api.md`.

The Profissional plan costs R$ 29,90 per month by default (`STRIPE_PROFESSIONAL_PRICE`), billed in BRL.

## Configuration

All billing settings live in `config/stripe.php` and are read from `.env`. Application code reads price and promo values only through `App\Interfaces\Services\PlanPricingInterface` (`App\Services\PlanPricing`). Views inject it with `@inject('pricing', \App\Interfaces\Services\PlanPricingInterface::class)`. No view or class hard-codes a price; `tests/Feature/Billing/NoHardcodedPriceTest.php` guards this.

| Env key | Config key | Purpose |
| --- | --- | --- |
| `STRIPE_PUBLISHABLE_KEY` | `stripe.publishable_key` | Publishable API key |
| `STRIPE_SECRET_KEY` | `stripe.secret_key` | Secret API key (server only) |
| `STRIPE_WEBHOOK_SECRET` | `stripe.webhook_secret` | Signing secret of the webhook endpoint |
| `STRIPE_PROFESSIONAL_PRICE_ID` | `stripe.plans.professional.stripe_price_id` | Stripe price charged at checkout |
| `STRIPE_PROFESSIONAL_PRICE` | `stripe.plans.professional.price` | Price shown in the UI (default `29.90`) |
| `STRIPE_PROMO_ENABLED` | `stripe.promo.enabled` | Turns the optional promo on (default `false`) |
| `STRIPE_PROMO_PRICE` | `stripe.promo.price` | Promo monthly price |
| `STRIPE_PROMO_ENDS_AT` | `stripe.promo.ends_at` | Promo end, e.g. `2026-12-31 23:59:59` (app timezone) |
| `STRIPE_PROMO_PRICE_ID` | `stripe.promo.stripe_price_id` | Stripe price charged during the promo |
| `STRIPE_PROMO_LABEL` | `stripe.promo.label` | Badge text (default "Oferta especial") |

The currency (`BRL`) and billing interval (`month`) are fixed in `config/stripe.php`.

### Promo rule

`PlanPricing::isPromoActive()` returns true only when all of these hold:

1. `STRIPE_PROMO_ENABLED=true`;
2. the promo price is greater than zero and lower than the regular price;
3. `STRIPE_PROMO_ENDS_AT` parses to a date in the future;
4. `STRIPE_PROMO_PRICE_ID` is set.

When the promo is active, checkout uses the promo price id, so the price on screen is the price Stripe charges. The countdown (`resources/views/components/billing/promo-countdown.blade.php`) targets the real end date and renders nothing when the promo is off. The promo price must be created by hand in the Stripe Dashboard as a monthly BRL recurring price for the same product.

## Setup and key rotation

The test keys used during development were shared in a chat message. Treat them as exposed and roll them in the Stripe Dashboard (Developers > API keys) before going live.

1. Put the new keys in `.env` (never in `.env.example`, code, or logs): `STRIPE_PUBLISHABLE_KEY`, `STRIPE_SECRET_KEY`.
2. Run `php artisan config:clear` (or `php artisan config:cache` in production) after every `.env` change.
3. Run `php artisan stripe:setup`. It creates the product "Finestro Profissional" and a monthly BRL price whose amount is derived from `STRIPE_PROFESSIONAL_PRICE`, then prints the line `STRIPE_PROFESSIONAL_PRICE_ID=price_...`.
4. Copy that line into `.env` and clear the config cache again.

### Test key in production (Phase 5)

`App\Services\StripeConfigCheck` (`StripeConfigCheckInterface`) flags a production environment that still uses a test secret key (`app()->isProduction()` and `STRIPE_SECRET_KEY` starts with `sk_test_`):

- `AppServiceProvider::boot()` calls `reportIfMisconfigured()` in production. It writes one `Log::critical` per day (guarded by `Cache::add('finestro:stripe:test-key-warning', ...)`). The log never contains the key.
- Users with role `admin` see a red alert at the top of every authenticated page (`resources/views/components/layout/stripe-config-alert.blade.php`, included after the plan banner) asking to update `STRIPE_SECRET_KEY` on the server. Other users see nothing. There is no admin panel yet, so the alert lives in the authenticated layout.

Tests: `tests/Feature/Billing/StripeConfigCheckTest.php`.

Until step 4 is done, `STRIPE_PROFESSIONAL_PRICE_ID` still points to the old Stripe price. The UI shows R$ 29,90 while checkout would charge the old amount. Stripe prices are immutable, so a price change always means a new price id: change `STRIPE_PROFESSIONAL_PRICE`, run `stripe:setup` again, and update the id.

## Webhook

Endpoint: `POST /stripe/webhook` (route `stripe.webhook`, no auth, no CSRF, `throttle:60,1`).

Production: create the endpoint in the Stripe Dashboard (Developers > Webhooks) pointing to `https://<your-domain>/stripe/webhook`, subscribe to the events below, and put its signing secret in `STRIPE_WEBHOOK_SECRET`.

Local development with the Stripe CLI:

```bash
stripe listen --forward-to http://<host>/stripe/webhook
```

The CLI prints a signing secret (`whsec_…`). Copy it into `STRIPE_WEBHOOK_SECRET` and run `php artisan config:clear`.

### Handled events

| Event | Effect |
| --- | --- |
| `checkout.session.completed` | Re-reads the session from Stripe and activates the Profissional plan for the owner in `client_reference_id` (or `metadata.user_id`), only if that user exists |
| `invoice.paid` | Upserts one `paid` payment row for the invoice; sets the subscription active and updates the period |
| `invoice.payment_failed` | Sets the subscription to `past_due` and records a `failed` row (never overwrites a `paid` row) |
| `customer.subscription.updated` | Syncs status, period, `cancel_at_period_end` and `canceled_at` |
| `customer.subscription.deleted` | Sets status `canceled` and `current_period_end = ended_at ?? now()`, so access ends immediately |

Other event types return 200 and are recorded as processed. Events for an unknown local subscription are logged and ignored.

### Responses and idempotency

- `STRIPE_WEBHOOK_SECRET` empty: 400.
- Missing or invalid `Stripe-Signature`: 400, nothing written.
- Handler error: 500, so Stripe retries.
- Event already processed: 200 `Already processed`.

Each event id is stored in the `stripe_webhook_events` table (unique `stripe_event_id`). `SubscriptionService::handleWebhookEvent()` inserts the marker and runs the handler inside one `DB::transaction`. A handler exception rolls the marker back, so the retry is processed. Payment history holds one row per Stripe invoice (`PaymentHistoryRepository::upsertForInvoice`), so the success page and the `invoice.paid` webhook converge on the same row.

The code reads payloads of Stripe API `2026-04-22.dahlia` (stripe-php v20) through `App\Services\StripePayloadMapper`: subscription periods from `items.data[0].current_period_*` and the invoice subscription id from `parent.subscription_details.subscription`, with the legacy top-level fields as fallback.

## Pro access rules

`Subscription::isActive()` decides access. A user is Pro when the plan is professional, the period end is empty or in the future, and:

- the status is `active` or `trialing`; or
- the status is `canceled` and `current_period_end` is in the future (access continues until the paid period ends).

`past_due`, `incomplete` and expired subscriptions fall back to Free. A past-due user sees a "Pagamento pendente" banner linking to the Financeiro page.

## Plan management (Financeiro page)

The Financeiro page (`subscription.index`, `/assinatura`) shows the current plan, validity dates, a countdown to renewal or to the end of access, the plan usage, and the paginated payment history (10 per page) with links to the Stripe invoice.

- Subscribe: `POST subscription.checkout` redirects to Stripe Checkout. A user who is already Pro is sent back with a message.
- Cancel: `POST subscription.cancel` calls `SubscriptionService::cancel()`. For an active Profissional subscription it sets `cancel_at_period_end` in Stripe. The user keeps Pro until the period end, then Stripe ends the subscription and the `customer.subscription.deleted` webhook moves the user to Free. For a `past_due` or `unpaid` subscription there is no paid period to keep, so the page shows a "Pagamento pendente" block and the cancel is immediate (`StripeGatewayInterface::cancelImmediately()`): Stripe stops retrying the open invoice and the local row becomes `canceled` with `current_period_end = ended_at ?? now()`. The later `customer.subscription.deleted` webhook writes the same values. Confirmation uses the SweetAlert2 `data-confirm` hook.
- Resume: `POST subscription.resume` (`throttle:3,1`) clears a scheduled cancellation.

None of these routes take parameters. They act only on the authenticated user's own subscription.

The checkout success page (`subscription.success`) shows the confirmation only when the subscription grants Pro access. Any other status goes back to Financeiro with a message from `Subscription::ineligibleAccessMessage()`: "Pagamento pendente" for `past_due`/`unpaid`, "Pagamento ainda não confirmado" for `incomplete`, and "Assine novamente" for canceled or expired subscriptions. Stripe's `incomplete_expired` is mapped to `canceled` by `StripePayloadMapper::mapStatus()`, so it gets the "Assine novamente" message.

## Account deletion

Both deletion endpoints (`DELETE settings.account.delete` and `DELETE profile.destroy`) call `App\Services\AccountDeletionService`. If the user has a subscription that is still open in Stripe (`Subscription::isOpenInStripe()`: has a Stripe id and is not `canceled`/`incomplete_expired`), it is canceled immediately first. If Stripe fails, the account is not deleted, the user stays logged in, a pt-BR error is flashed, and `Log::error` records only the user id, subscription id and exception class.

Phase 5 changes:

- **Mode mismatch.** `StripeGateway::isModeMismatch()` detects Stripe's "a similar object exists in live/test mode" message (an `InvalidRequestException`, often with code `resource_missing`). `isResourceMissing()` returns false for it, so the subscription is not treated as gone: the deletion is blocked with the generic error and the subscription stays as it is. This happens when the app runs with keys of the other mode than the one the subscription was created in.
- **Log level.** A real `resource_missing` (the subscription no longer exists in Stripe) still marks the local subscription canceled and continues the deletion, but is now logged with `Log::error` instead of `Log::warning` (same context keys, no key values), because it usually means local and Stripe data diverged.
- **API tokens.** All of the user's personal access tokens are deleted right before the account is soft-deleted.

## Security fixes

- **Checkout success ownership (Phase 1 audit MUST-FIX).** Checkout sets `client_reference_id`, `metadata.user_id` and `subscription_data.metadata.user_id` to the user id. `SubscriptionService::handleCheckoutSuccess()` accepts a session only when it exists, its status is `complete`, it has a subscription, the owner id matches the authenticated user (`hash_equals`), and the customer matches the user's stored `stripe_customer_id` if there is one. Otherwise it throws `CheckoutSessionNotFoundException` and the controller returns 404 without writing anything. A missing `session_id` also returns 404. Covered by `tests/Feature/Billing/CheckoutSuccessTest.php`.
- **CSP form-action.** `App\Http\Middleware\SecurityHeaders` now sends `form-action 'self' https://checkout.stripe.com`. Browsers apply `form-action` to the redirect that follows `POST /assinatura/checkout`, so without it the redirect to Stripe Checkout was blocked.
- **Lazy Stripe client.** `StripeGateway` creates the `StripeClient` on first use and throws a clear error when the secret key is empty, instead of failing at construction.

## Tests

No test calls Stripe. `phpunit.xml` forces empty Stripe keys, `STRIPE_PROFESSIONAL_PRICE_ID=price_test_professional` and `STRIPE_PROMO_ENABLED=false`.

- `Tests\Fakes\FakeStripeGateway` implements `App\Interfaces\Services\StripeGatewayInterface`. Tests bind it with `$this->app->instance(StripeGatewayInterface::class, $fake)`, register sessions in `$fake->sessions`, and assert on `$fake->calls`.
- Webhook tests set `stripe.webhook_secret` in config and sign payloads locally (`t=<time>,v1=<HMAC-SHA256>`); the fake delegates verification to `Stripe\Webhook::constructEvent`, which is a local check.

Billing test files in `tests/Feature/Billing/`: `PlanPricingTest`, `CheckoutSuccessTest`, `StripeWebhookTest`, `PlanGatingTest`, `PlanManagementTest`, `FinanceiroPageTest`, `NoHardcodedPriceTest`, plus the Pest files `CheckoutSuccessStatusTest` and `PastDueCancelTest`. `tests/Feature/SubscriptionServiceTest.php` covers the service; `tests/Feature/AccountDeletionTest.php` and `tests/Feature/RecurringPlanCapTest.php` (Pest) cover account deletion and the recurring cap.

## Known gaps

Fixed in Phase 3:

- Account deletion cancels the Stripe subscription first. When Stripe answers `resource_missing` (the subscription no longer exists there), the local subscription is marked canceled and the deletion continues; any other Stripe failure still blocks the deletion with a pt-BR error (`tests/Feature/AccountDeletionTest.php`).
- `past_due`/`unpaid` subscribers can cancel from the Financeiro page; the cancellation is immediate (`tests/Feature/Billing/PastDueCancelTest.php`).
- `ProcessRecurringTransactions` respects the monthly transaction cap of the owner's plan. An occurrence that would exceed it is skipped, logged (`Recurring transaction skipped: monthly plan limit reached`) and shown as `[limite]` in the command output. Skipped dates are retried on later runs only while no later occurrence of the series exists. Once a later occurrence is created (e.g. next month), the skipped dates are not generated, even after an upgrade (`tests/Feature/RecurringPlanCapTest.php`).
- Recurring series whose owner account is soft-deleted are skipped entirely (`tests/Feature/RecurringMissingOwnerTest.php`).
- The checkout success page shows a per-status message for every non-Pro result (`tests/Feature/Billing/CheckoutSuccessStatusTest.php`).

Still open:

- `User::subscription()` uses `latestOfMany()` on random UUIDs. It is correct today only because `updateOrCreateForUser` keeps exactly one subscription row per user. If a second row is ever inserted for the same user, `latestOfMany()` on a random UUID has no meaningful ordering key and `subscription()` becomes non-deterministic. Any change that can create a second row must first switch this to a deterministic order (e.g. `latestOfMany('created_at')`).
- The `plan.limit` middleware (`App\Http\Middleware\CheckPlanLimit`, alias in `bootstrap/app.php`) is kept although no production route uses it. `PlanGatingTest::test_plan_limit_middleware_message_uses_configured_price` exercises it through a probe route, and it remains available for route-level gating.
