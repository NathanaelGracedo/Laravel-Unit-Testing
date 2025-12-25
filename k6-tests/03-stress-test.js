import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metrics
let errorRate = new Rate('errors');

// Stress test - gradually increase load until system breaks
export let options = {
    stages: [
        { duration: '1m', target: 20 },   // Warm up
        { duration: '2m', target: 50 },   // Normal load
        { duration: '2m', target: 100 },  // High load
        { duration: '2m', target: 200 },  // Very high load (stress)
        { duration: '1m', target: 0 },    // Cool down
    ],
    thresholds: {
        'http_req_duration': ['p(95)<2000'], // Allow higher latency
        'errors': ['rate<0.3'],              // Allow 30% error in stress
    },
};

const BASE_URL = 'http://localhost';

export default function () {
    // Scenario 1: Login attempt
    let loginRes = http.post(`${BASE_URL}/login`, {
        username: `user${__VU}`,
        password: 'password123',
    });
    
    check(loginRes, {
        'login handled': (r) => r.status >= 200 && r.status < 500,
    }) || errorRate.add(1);

    sleep(1);

    // Scenario 2: Browse fasilitas
    let browseRes = http.get(`${BASE_URL}/admin/fasilitas`);
    
    check(browseRes, {
        'browse handled': (r) => r.status >= 200 && r.status < 500,
    }) || errorRate.add(1);

    sleep(1);

    // Scenario 3: Create fasilitas
    let createRes = http.post(`${BASE_URL}/admin/fasilitas`, 
        JSON.stringify({
            fasilitas_nama: `Stress Test ${__VU}-${Date.now()}`,
            ruangan_id: 1,
        }),
        { headers: { 'Content-Type': 'application/json' }}
    );
    
    check(createRes, {
        'create handled': (r) => r.status >= 200 && r.status < 500,
        'not server error': (r) => r.status !== 500,
    }) || errorRate.add(1);

    sleep(0.5);
}

export function handleSummary(data) {
    return {
        'k6-tests/results/03-stress-test-summary.json': JSON.stringify(data),
    };
}
