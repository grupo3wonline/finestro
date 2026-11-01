// Load: ramps to 20 virtual users and holds them, about 5 minutes in total.
// Represents a normal busy period.
//
//   k6 run -e BASE_URL=http://finestro.test tests/load/load.js

import { assertSafeTarget } from './lib/config.js';
import { journey } from './lib/flows.js';

export const options = {
    stages: [
        { duration: '1m', target: 20 },
        { duration: '3m', target: 20 },
        { duration: '1m', target: 0 },
    ],
    thresholds: {
        http_req_failed: ['rate<0.01'],
        http_req_duration: ['p(95)<800'],
    },
};

export function setup() {
    return assertSafeTarget();
}

export default function () {
    journey(1);
}
