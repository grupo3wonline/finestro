# Stripe billing: ownership-checked checkout, idempotent webhooks, Financeiro area and config-driven pricing (review pass 2)

Phase 2 replaces the hard-coded R$ 24,90 / R$ 41,58 / "40% OFF" pricing with one config-driven price (`config/stripe.php`, read only through `App\Services\PlanPricing`), an optional honest promo that is off by default, and a Stripe integration that keeps local subscription state in sync. Checkout success accepts only complete sessions owned by the authenticated user and answers 404 with no write otherwise. Webhooks verify the signature, deduplicate through `stripe_webhook_events` inside a DB transaction, and handle the five required events with amounts from the invoice. This pass checks the fixes for the five issues raised in pass 1. Four are fixed in code with new tests, and the past_due cancel gap is documented as deferred in `docs/billing/phase-2-stripe.md`. The recorded evidence (FEAT-003 findings, iteration 2) shows 306 tests / 952 assertions passing twice (once with `--order-by=random`, seed 1793320171), clean `php -l`, `route:list`, `view:cache` and `npm run build`, and zero price-grep hits. A fresh grep in this pass found no `41,58`/`24,90`/`24.90` and no key-like `sk_/pk_/rk_/whsec_` strings in app, config, resources, docs or `.env.example`.

Follow-up from pass 2 applied: the success-page replay now redirects for **any** non-Pro status, not only `canceled`. A `past_due`/`unpaid`/`incomplete` replay is sent back to Financeiro with a status-specific message instead of the "Assinatura confirmada! Todos os recursos estão liberados" page. Nothing outstanding.

**Verdict**: APPROVED

## High-level view

The success-page replay hole is closed. `StripeGateway::retrieveCheckoutSession` now returns `ended_at` and the item `price_id`. When the session's subscription maps to `canceled`, `activateFromCheckout` stores `current_period_end = ended_at ?? now()`, the same rule `customer.subscription.deleted` uses. An ended session of a replaced subscription (different `stripe_subscription_id`) leaves the current row alone. Late `checkout.session.completed` retries take the same path.

The Financeiro card and the renewal sentence now show `SubscriptionService::chargedPrice()`. That value is the latest paid invoice amount of the user's own subscription (scoped by `user_id` and `subscription_id`), with the config price as fallback. `stripe_price_id` now comes from the session's subscription item, with config only as a fallback. The webhook failure path has a test: a gateway exception during `checkout.session.completed` returns 500 and leaves no `stripe_webhook_events` or `subscriptions` row, and a retry of the same event id then succeeds.

The controller's replay guard now redirects on any non-Pro status. The success view renders only when `isProfessional()` is true.

<details>
<summary>Issues (0)</summary>

No open issues. The pass 2 follow-up (success page confirming a `past_due`/`incomplete` replay) is resolved: `SubscriptionController::success` now redirects whenever `! $subscription->isProfessional()`, with a `match` on status that picks "Há um pagamento pendente…" for `past_due`/`unpaid`, "O pagamento ainda não foi concluído…" for `incomplete`/`incomplete_expired`, and the ended-subscription message otherwise.

</details>

<details>
<summary>Details</summary>

### Replay guard in activateFromCheckout

```
session subscription status (live) -> mapStatus
  canceled / incomplete_expired:
     existing row has another stripe_subscription_id -> return existing, no write
     otherwise -> status=canceled, current_period_end=ended_at ?? now(), cancel_at_period_end=false
  anything else -> live status, live period, live cancel_at_period_end
controller: ! isProfessional() -> redirect Financeiro + status-specific error
            else                 -> subscription.success view
```

`CheckoutSuccessTest` covers the replay after `customer.subscription.deleted` (period end stays in the past, user stays Free), an old ended session that must not overwrite a newer active subscription, and a `past_due` replay that redirects without confirming.

The non-canceled branch still copies live Stripe state on every replay. For `past_due` it writes `past_due`, so access is correct (Free). The controller now guards on `! isProfessional()` rather than only `canceled`, so a `past_due`/`unpaid`/`incomplete` replay redirects to Financeiro with its own message instead of the success view. Confidence: confirmed (`SubscriptionController::success` condition and the new `CheckoutSuccessTest::test_replaying_success_for_a_past_due_subscription_does_not_confirm`).

### Test coverage

Pass 2 added tests for the replay after deletion, the replaced-subscription guard, the session price id, the latest paid invoice amount on Financeiro, and the 500/rollback/retry webhook path. The pass 2 follow-up added `test_replaying_success_for_a_past_due_subscription_does_not_confirm`. The DONE-MEANS items keep their pass 1 coverage: success ownership, webhook signature, idempotency and each event, the 6-state gating matrix, own-only paginated Financeiro with validity and countdown, upgrade/cancel/resume through `FakeStripeGateway`, and the hard-coded price scan. `CheckoutSuccessTest` passes locally: 12 tests / 42 assertions.

</details>

<details>
<summary>File map</summary>

Changed in pass 2:

- `app/Services/StripeGateway.php`, `app/Interfaces/Services/StripeGatewayInterface.php`: session data includes `ended_at` and `price_id`.
- `app/Services/StripePayloadMapper.php`: `subscriptionData` returns the item `price_id`.
- `app/Services/SubscriptionService.php`, `app/Interfaces/Services/SubscriptionServiceInterface.php`: ended-session handling in `activateFromCheckout`, price id from the session, new `chargedPrice()`.
- `app/Repositories/PaymentHistoryRepository.php` (+ interface): `latestPaidForSubscription`.
- `app/Http/Controllers/SubscriptionController.php`: passes `chargedPrice` to Financeiro; `success()` redirects any non-Pro replay (canceled, past_due/unpaid, incomplete) with a status-specific message.
- `resources/views/subscription/index.blade.php`: plan card and renewal text use the charged price.
- `tests/Fakes/FakeStripeGateway.php`: `retrieveException`, `ended_at`/`price_id` defaults.
- `tests/Feature/Billing/CheckoutSuccessTest.php`, `StripeWebhookTest.php`, `FinanceiroPageTest.php`: new regression tests, including the `past_due` replay guard.
- `docs/billing/phase-2-stripe.md`: past_due cancel listed under known gaps.

Unchanged since pass 1: `config/stripe.php`, `PlanPricing` (+ interface), migration `2026_11_01_000100_add_billing_sync_fields.php`, `StripeWebhookEvent` model/repository, `CheckPlanLimit`, `SecurityHeaders`, `StripeSetup`, `routes/web.php`, header/plan-banner/CTA/landing/locked/plans/promo-countdown views, `.env.example`, `docs/security/phase-1-acl-audit.md`. Full diff: `git diff` plus the untracked Phase 2 files.

</details>
