// Spike: jumps from a few users to 150 virtual users for 30 seconds, then
// drops back. Checks that the app survives a sudden burst and recovers.
//
//   k6 run -e BASE_URL=http://finestro.test tests/load/spike.js

import { assertSafeTarget } from './lib/config.js';
import { journey } from './lib/flows.js';

export const options = {
    stages: [
        { duration: '30s', target: 5 },
        { duration: '10s', target: 150 },
        { duration: '30s', target: 150 },
        { duration: '10s', target: 5 },
        { duration: '30s', target: 5 },
        { duration: '10s', target: 0 },
    ],
    thresholds: {
        http_req_failed: ['rate<0.10'],
        http_req_duration: ['p(95)<3000'],
    },
};

export function setup() {
    return assertSafeTarget();
}

export default function () {
    journey(0.5);
}
