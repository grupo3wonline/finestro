// Smoke: 1 virtual user for 30 seconds. Proves the flows work before any
// heavier profile is run.
//
//   k6 run -e BASE_URL=http://finestro.test tests/load/smoke.js

import { assertSafeTarget } from './lib/config.js';
import { journey } from './lib/flows.js';

export const options = {
    vus: 1,
    duration: '30s',
    thresholds: {
        http_req_failed: ['rate<0.01'],
        http_req_duration: ['p(95)<800'],
        checks: ['rate>0.99'],
    },
};

export function setup() {
    return assertSafeTarget();
}

export default function () {
    journey(1);
}
