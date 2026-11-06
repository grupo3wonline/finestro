# Phase 8: bot protection, e-mail activation, password aging and reminders

Date: 2026-11-01. Scope: Google reCAPTCHA v3 and Cloudflare Turnstile on the guest forms, HostGator SMTP for account e-mails, accounts that are active only after e-mail confirmation, a 90-day password lifetime with a 7-day warning, and a 7-day warning before the Professional subscription renews.

## Bot protection (reCAPTCHA v3 + Turnstile)

Forms protected (all guest forms): register, login, forgot password, reset password, and the "resend confirmation e-mail" form. Authenticated forms are already behind the session, CSRF and the route throttles, so they carry no captcha.

Each provider is **enabled only when both its site key and its secret key are set**. With both providers configured, a form has to pass both. With no keys the protection is off (local development, test suite); the existing honeypot keeps running on the register form.

| Setting | Meaning |
| --- | --- |
| `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY` | Google reCAPTCHA v3 (type "Score based") |
| `RECAPTCHA_MIN_SCORE` | Minimum score from 0.0 (bot) to 1.0 (human). Default `0.5` |
| `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` | Cloudflare Turnstile |
| `CAPTCHA_TIMEOUT` | Seconds to wait for the provider. Default `5` |

How it works:

- `config/captcha.php` holds the settings; `App\Services\CaptchaService` (interface `CaptchaServiceInterface`) calls the `siteverify` endpoints and builds the validation rules.
- `App\Rules\RecaptchaV3` and `App\Rules\TurnstileToken` are implicit rules, so a missing token is rejected too. reCAPTCHA also checks that the token was issued for the same **action** as the form (`login`, `register`, `forgot_password`, `reset_password`, `resend_verification`) and that the score reaches the minimum.
- The check **fails closed**: a timeout, a non-2xx answer or a malformed answer rejects the form (and logs a warning without the token).
- Controllers and `LoginRequest` add `...$captcha->rules('<action>')` to their validation rules.
- `<x-ui.captcha action="..." />` renders the widgets. reCAPTCHA is invisible: the script fills a hidden `g-recaptcha-response` on load, renews it every 100 seconds (tokens last 2 minutes) and fetches a fresh one on submit if needed. Turnstile adds its own `cf-turnstile-response`; submitting before it finishes shows a "wait" hint instead of posting.
- `SecurityHeaders` allows `www.google.com/recaptcha/`, `www.gstatic.com/recaptcha/`, `recaptcha.google.com` and `challenges.cloudflare.com` in `script-src`, `frame-src` and `connect-src` where needed.
- The privacy policy lists the configured provider(s) as operators.

Local testing: Cloudflare publishes dummy Turnstile keys (site `1x00000000000000000000AA`, secret `1x0000000000000000000000000000000AA`) that always pass. reCAPTCHA v3 has no official dummy keys; create a key for `localhost` in the admin console.

## E-mail through HostGator SMTP

Registration confirmation, account activation and password reset are queued notifications (`QueuedVerifyEmail`, `QueuedResetPassword`), as are the two reminders below. Their texts are in Brazilian Portuguese (`lang/pt_BR.json` translates the framework's mail template and the stock strings).

1. In cPanel create a mailbox for the sender, for example `noreply@finestro.com.br`.
2. Put it in `.env` (the same address in `MAIL_USERNAME` and `MAIL_FROM_ADDRESS`):

```
MAIL_MAILER=smtp
MAIL_HOST=mail.finestro.com.br
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=noreply@finestro.com.br
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="noreply@finestro.com.br"
MAIL_TIMEOUT=30
```

Port 465 uses implicit TLS (`MAIL_SCHEME=smtps`). Port 587 uses STARTTLS (leave `MAIL_SCHEME` empty). If the connection fails with a certificate mismatch, use the server host name shown in cPanel ("Connect Devices") as `MAIL_HOST`.

3. E-mails are queued, so something has to process the queue: a Supervisor worker on a VPS, or `QUEUE_RUN_VIA_SCHEDULER=true` plus the `schedule:run` cron line on shared hosting (see the README). Without it nothing is sent.
4. Check SPF and DKIM for the domain in cPanel ("Email Deliverability"), otherwise Gmail and Outlook tend to send these messages to spam.

Quick check from the server: `php artisan tinker --execute="Mail::raw('teste', fn(\$m) => \$m->to('voce@exemplo.com')->subject('Teste SMTP'));"` (queued mail is not involved in this one).

## Accounts are active only after e-mail confirmation

- Registration creates the user and sends the confirmation link, but **does not sign the user in**. They are sent to the login page with an explanation.
- Login refuses an unconfirmed account. With the right credentials the message is `User::UNVERIFIED_MESSAGE` and a link to resend the e-mail; with wrong credentials the generic error is shown, so the screen does not reveal which accounts exist. Suspended accounts keep their own message, which takes precedence.
- The link in the e-mail (`verification.verify`) **no longer needs a session**: it is a signed URL and the hash must match the account e-mail. It marks the e-mail as verified, fires `Verified` (wallet invitations are claimed there) and sends the person to the login page. The link lasts `AUTH_VERIFICATION_EXPIRE` minutes (default 1440, 24 hours).
- `/reenviar-confirmacao` (`verification.resend.*`) sends a new link. It answers the same way for unknown, confirmed and throttled addresses, is limited to 3 e-mails per address per hour plus `throttle:6,1`, and uses the captcha.
- The `verified` middleware on the app routes, the API checks and the admin panel (`canAccessPanel`) were already in place.

Accounts created before this change that are logged in without having confirmed keep working only on the verification notice page, as before.

## Password lifetime: 90 days, warning at 7

| Setting | Default |
| --- | --- |
| `PASSWORD_MAX_AGE_DAYS` | `90` |
| `PASSWORD_WARNING_DAYS` | `7` |

- Migration `2026_12_02_000100_add_password_policy_to_users_table` adds `users.password_changed_at` and `users.password_expiry_reminded_at`. **Existing accounts start their 90 days on the day the migration runs**, so nobody is locked out on deploy.
- The `User` model sets `password_changed_at` whenever the `password` attribute changes (registration, profile, settings, reset link, forced change, admin tools). Saving anything else leaves it alone.
- `EnsurePasswordIsNotExpired` (alias `password.fresh`) wraps the app route groups. An expired password redirects every app page to `/senha-expirada`, which only shows the "change password" form and logout. The new password must differ from the current one (the same rule now applies to the settings form and the Breeze `PUT /password`). The API is not affected.
- Inside the last 7 days: a dismissible banner on every app page (`components/layout/password-banner`) and a note with the expiry date in Configurações > Alterar Senha.
- `php artisan security:remind-password-expiry` (daily at 09:00) e-mails each verified, non-suspended user once per password when it enters the window. `password_expiry_reminded_at` older than `password_changed_at` means "not reminded yet", so a new password starts a new cycle.

## Subscription renewal reminder: 7 days before

| Setting | Default |
| --- | --- |
| `RENEWAL_WARNING_DAYS` | `7` |

- Migration `2026_12_02_000200_add_renewal_reminder_to_subscriptions_table` adds `subscriptions.renewal_reminded_for`.
- A Professional subscription that grants access and whose `current_period_end` is within 7 days shows a banner (`plan-banner`): "renova em N dias" for a normal renewal, or "termina em N dias" with a "Retomar assinatura" link when `cancel_at_period_end` is set. Past-due and expired subscriptions keep their existing red banners.
- `php artisan billing:remind-renewals` (daily at 09:00) sends the matching e-mail once per billing period. `renewal_reminded_for` stores the period end already reminded, so the next period triggers a new reminder. The e-mail does not state an amount, because the renewal price is whatever Stripe holds for that subscription.

Both commands rely on the scheduler cron line and on the queue.

## Files

- Captcha: `config/captcha.php`, `app/Services/CaptchaService.php`, `app/Interfaces/Services/CaptchaServiceInterface.php`, `app/Rules/RecaptchaV3.php`, `app/Rules/TurnstileToken.php`, `resources/views/components/ui/captcha.blade.php`, `app/Http/Middleware/SecurityHeaders.php`.
- Activation: `RegisteredUserController`, `LoginRequest`, `VerifyEmailController`, `ResendVerificationController`, `resources/views/auth/resend-verification.blade.php`, `routes/auth.php`, `config/auth.php` (`verification.expire`), `lang/pt_BR.json`, `QueuedVerifyEmail`.
- Password aging: `config/security.php`, `app/Models/User.php`, `EnsurePasswordIsNotExpired`, `ExpiredPasswordController`, `resources/views/auth/password-expired.blade.php`, `PasswordExpiringNotification`, `RemindPasswordExpiry`.
- Renewal: `app/Models/Subscription.php`, `SubscriptionRenewalNotification`, `RemindSubscriptionRenewals`, `AccountReminderService`.
- Schedule: `routes/console.php`.

## How to revert

Both migrations have `down()` methods (`php artisan migrate:rollback --step=2`). To turn the features off without rolling back: leave the captcha keys empty; set `PASSWORD_MAX_AGE_DAYS` to a very large number; remove the two `Schedule::command` lines for the reminders. Restoring the old "sign in right after sign-up" behaviour means re-adding `Auth::login($user)` in `RegisteredUserController` and dropping the verified check from the `attemptWhen` callback in `LoginRequest`.

## Tests

The four existing tests that expected registration to open a session were updated (`RegistrationTest`, `PolicyVersionGateTest`, `RolePermissionTest`, `UserJourneyTest`). Four new files cover the phase (62 tests); the provider endpoints are always faked with `Http::fake`, so nothing reaches the network:

| File | Covers |
| --- | --- |
| `tests/Feature/Security/CaptchaTest.php` | Providers off without keys; missing token; wrong action, low score, provider error and unreachable provider (fail closed); `RECAPTCHA_MIN_SCORE`; both providers required together; a captcha failure does not count as a failed login; all five guest forms; widget markup and actions; secrets never rendered; CSP origins; privacy policy listing |
| `tests/Feature/Auth/EmailActivationTest.php` | No session after sign-up; unconfirmed login blocked (and the generic error with a wrong password); suspended message first; link without a session; tampered, expired and unsigned links; 24-hour validity; resend form (neutral answer, 3 per hour); pt-BR e-mail texts |
| `tests/Feature/Security/PasswordAgingTest.php` | Clock start and restart (including reset link); days-left table around the 7-day window and day 90; lock-out and forced change screen; different-password rule; banner; settings page; reminder once per password and again after a change; schedule at 09:00 |
| `tests/Feature/Billing/RenewalReminderTest.php` | Banner inside/outside the window and the cancellation variant; past-due and expired keep their banners; free users; reminder once per period and again for the next; excluded users; e-mail texts without a price |

The full suite passes (1074 tests, 15 skipped, after phase 10) and `composer quality` exits 0.
