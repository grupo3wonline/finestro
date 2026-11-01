// Shared k6 configuration for the Finestro load tests.
//
// Every value comes from environment variables (k6 run -e NAME=value ...).
// The target defaults to the local Laragon host and a guard refuses any
// host that is not local unless K6_ALLOW_STAGING=1 is set explicitly.
// Never point these scripts at production.

import exec from 'k6/execution';

export const BASE_URL = (__ENV.BASE_URL || 'http://finestro.test').replace(/\/+$/, '');

export const CREDENTIALS = {
    username: __ENV.K6_USERNAME || '',
    email: __ENV.K6_EMAIL || '',
    password: __ENV.K6_PASSWORD || '',
};

export const API_TOKEN = __ENV.K6_API_TOKEN || '';

export const hasCredentials = () => CREDENTIALS.username !== '' && CREDENTIALS.email !== '' && CREDENTIALS.password !== '';

export const hasApiToken = () => API_TOKEN !== '';

/** Host part of BASE_URL, lower-cased, without port. */
export function targetHost(url = BASE_URL) {
    const match = /^https?:\/\/([^/:?#]+)/i.exec(url);

    return match ? match[1].toLowerCase() : '';
}

/** True for localhost, 127.0.0.1, ::1, *.test and *.local. */
export function isLocalHost(host) {
    return host === 'localhost'
        || host === '127.0.0.1'
        || host === '[::1]'
        || host.endsWith('.test')
        || host.endsWith('.local');
}

/**
 * Aborts the whole test run unless the target is local or staging was
 * explicitly allowed. Call it from setup() of every script.
 */
export function assertSafeTarget() {
    const host = targetHost();

    if (host === '') {
        exec.test.abort(`BASE_URL "${BASE_URL}" is not a valid http(s) URL.`);
    }

    if (!isLocalHost(host) && __ENV.K6_ALLOW_STAGING !== '1') {
        exec.test.abort(
            `Refusing to load-test "${host}". Only localhost, 127.0.0.1, *.test and *.local are allowed. `
            + 'Set K6_ALLOW_STAGING=1 for a staging host you own. Never target production.',
        );
    }

    return { host, authenticated: hasCredentials(), api: hasApiToken() };
}

/** Default request tags so results can be grouped per flow. */
export const tags = (name) => ({ tags: { flow: name } });
