# Load tests (k6)

Scripts for [Grafana k6](https://grafana.com/docs/k6/latest/). They are not part of
`php artisan test`; run them by hand against a local or staging copy of Finestro.

> **Never run these scripts against production.** `lib/config.js` aborts the run
> unless the host is `localhost`, `127.0.0.1`, `*.test` or `*.local`. A staging host
> you own needs the explicit opt-in `K6_ALLOW_STAGING=1`.

k6 is **not installed** on the development machine where these scripts were written,
so they have not been executed there. They were only syntax-checked with
`node --check`.

## Install k6

Pick one:

```powershell
winget install k6 --source winget
choco install k6
docker pull grafana/k6
```

## Files

| File | Profile | Thresholds |
| --- | --- | --- |
| `smoke.js` | 1 VU for 30 s | `http_req_failed` < 1 %, `p(95)` < 800 ms, checks > 99 % |
| `load.js` | ramp to 20 VUs, about 5 min | `http_req_failed` < 1 %, `p(95)` < 800 ms |
| `stress.js` | steps up to 100 VUs, about 8 min | `http_req_failed` < 5 %, `p(95)` < 2000 ms |
| `spike.js` | burst to 150 VUs for 30 s | `http_req_failed` < 10 %, `p(95)` < 3000 ms |
| `lib/config.js` | `BASE_URL`, credentials, token, host guard | |
| `lib/flows.js` | landing, login, dashboard, API flows | |

Each iteration runs:

1. `GET /` (landing).
2. Login, once per VU: `GET /login`, read the CSRF token from
   `<meta name="csrf-token">` (or the `_token` input), then `POST /login` with
   **username + e-mail + password**. Skipped when no credentials are given.
3. `GET /dashboard` with the VU's session cookie. Skipped without credentials.
4. `GET /api/v1/wallets` with `Authorization: Bearer <token>`. Skipped without a token.

## Environment variables

| Variable | Default | Meaning |
| --- | --- | --- |
| `BASE_URL` | `http://finestro.test` | Target app URL. |
| `K6_USERNAME`, `K6_EMAIL`, `K6_PASSWORD` | empty | Login of a dedicated test account. |
| `K6_API_TOKEN` | empty | Personal token from **Configurações > Tokens de API** (Profissional plan). |
| `K6_ALLOW_STAGING` | unset | Set to `1` to allow a non-local host you own. |

## Run

Local (Laragon), anonymous traffic only:

```powershell
k6 run tests/load/smoke.js
```

Local with login and API:

```powershell
k6 run -e K6_USERNAME=carga -e K6_EMAIL=carga@example.com -e K6_PASSWORD=... -e K6_API_TOKEN=1|abc... tests/load/load.js
```

Staging (a host you own, never production):

```powershell
k6 run -e BASE_URL=https://staging.example.com -e K6_ALLOW_STAGING=1 -e K6_USERNAME=... -e K6_EMAIL=... -e K6_PASSWORD=... tests/load/stress.js
```

Docker (the container reaches the host through `host.docker.internal`, which the
guard does not treat as local, so the opt-in is required):

```powershell
docker run --rm -i -v "${PWD}/tests/load:/scripts" -e BASE_URL=http://host.docker.internal -e K6_ALLOW_STAGING=1 grafana/k6 run /scripts/smoke.js
```

## Rate limits you will hit

The app throttles on purpose. Use dedicated test accounts and read the results with
these limits in mind:

- Login: 5 failed attempts per username + e-mail + IP per minute (`LoginRequest`).
  The scripts sign in once per VU and reuse the session, so successful runs stay
  under it.
- `POST /register` and `POST /forgot-password`: 6 per minute per IP. The scripts
  do not call them.
- API: `API_RATE_LIMIT_PER_MINUTE` per token (default 60) and
  `API_IP_RATE_LIMIT_PER_MINUTE` per IP (default 120). Every VU shares the one
  token, so expect `429` from `load.js` upward. The API request declares `429` as
  an expected status, so it does not count in `http_req_failed`; the
  `api not throttled` check shows how many calls were throttled. Raise the limits
  on a staging copy if you want to measure the API itself.
- Web pages have no global rate limit; `429` on `/` or `/dashboard` would be a
  finding.

## Reading results

- `http_req_duration p(95)`: 95 % of requests finished within this time.
- `http_req_failed`: share of responses with status >= 400.
- `checks`: share of passed `check()` assertions (status, CSRF token found,
  login landed on `/dashboard`).
- Group timings (`landing`, `login`, `dashboard`, `api`) show which flow is slow.
