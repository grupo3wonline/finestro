// Stress: climbs step by step to 100 virtual users to find where latency
// and errors start to grow. Expect 429 from the rate limiters at the top.
//
//   k6 run -e BASE_URL=http://finestro.test tests/load/stress.js

import { assertSafeTarget } from './lib/config.js';
import { journey } from './lib/flows.js';

export const options = {
    stages: [
        { duration: '1m', target: 25 },
        { duration: '2m', target: 50 },
        { duration: '2m', target: 100 },
        { duration: '2m', target: 100 },
        { duration: '1m', target: 0 },
    ],
    thresholds: {
        http_req_failed: ['rate<0.05'],
        http_req_duration: ['p(95)<2000'],
    },
};

export function setup() {
    return assertSafeTarget();
}

export default function () {
    journey(0.5);
}
