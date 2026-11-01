// User flows exercised by every load profile.
//
// - landing:   GET /
// - login:     GET /login, read the CSRF token, POST username + e-mail + password
// - dashboard: GET /dashboard with the session cookie of the VU
// - api:       GET /api/v1/wallets with a Sanctum personal access token
//
// k6 keeps one cookie jar per VU, so a VU signs in once and reuses the
// session on later iterations (repeated logins would also hit the login
// rate limit: 5 failed attempts per username + e-mail + IP per minute).

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { API_TOKEN, BASE_URL, CREDENTIALS, hasApiToken, hasCredentials, tags } from './config.js';

let signedIn = false;

/** CSRF token from <meta name="csrf-token"> or the hidden _token input. */
export function csrfToken(html) {
    const body = html || '';
    const meta = /<meta\s+name="csrf-token"\s+content="([^"]+)"/i.exec(body);
    if (meta) {
        return meta[1];
    }

    const input = /<input[^>]+name="_token"[^>]+value="([^"]+)"/i.exec(body);

    return input ? input[1] : '';
}

export function landing() {
    group('landing', () => {
        const res = http.get(`${BASE_URL}/`, tags('landing'));
        check(res, {
            'landing 200': (r) => r.status === 200,
            'landing has footer credit': (r) => (r.body || '').includes('HOTWYL | WILLFROMBRASIL'),
        });
    });
}

export function login() {
    if (!hasCredentials() || signedIn) {
        return signedIn;
    }

    group('login', () => {
        const page = http.get(`${BASE_URL}/login`, tags('login'));
        const token = csrfToken(page.body);
        check(page, {
            'login page 200': (r) => r.status === 200,
            'login page has csrf token': () => token !== '',
        });

        const res = http.post(`${BASE_URL}/login`, {
            _token: token,
            username: CREDENTIALS.username,
            email: CREDENTIALS.email,
            password: CREDENTIALS.password,
        }, tags('login'));

        // Redirects are followed: a successful login lands on /dashboard.
        signedIn = check(res, {
            'login lands on dashboard': (r) => r.status === 200 && r.url.includes('/dashboard'),
        });
    });

    return signedIn;
}

export function dashboard() {
    if (!login()) {
        return;
    }

    group('dashboard', () => {
        const res = http.get(`${BASE_URL}/dashboard`, tags('dashboard'));
        check(res, { 'dashboard 200': (r) => r.status === 200 });
    });
}

export function api() {
    if (!hasApiToken()) {
        return;
    }

    group('api', () => {
        const res = http.get(`${BASE_URL}/api/v1/wallets`, {
            headers: { Authorization: `Bearer ${API_TOKEN}`, Accept: 'application/json' },
            tags: { flow: 'api' },
            // The per-token limit (60/min by default) is shared by every VU:
            // a 429 here is the limiter working, not a failure of the app.
            responseCallback: http.expectedStatuses(200, 429),
        });
        check(res, { 'api not throttled': (r) => r.status === 200 });
        check(res, {
            'api 200 (or 429 when throttled)': (r) => r.status === 200 || r.status === 429,
        });
    });
}

/** One iteration of the default mix. */
export function journey(pause = 1) {
    landing();
    dashboard();
    api();
    sleep(pause);
}
