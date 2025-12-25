import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metrics
let errorRate = new Rate('errors');

// Test configuration
export let options = {
    stages: [
        { duration: '30s', target: 10 },  // Ramp up to 10 users
        { duration: '1m', target: 30 },   // Stay at 30 users
        { duration: '30s', target: 50 },  // Spike to 50 users
        { duration: '30s', target: 0 },   // Ramp down to 0
    ],
    thresholds: {
        'http_req_duration': ['p(95)<500'], // 95% requests harus < 500ms
        'errors': ['rate<0.1'],             // Error rate harus < 10%
    },
};

const BASE_URL = 'http://localhost';

export default function () {
    // Test 1: Access Login Page
    let loginPageRes = http.get(`${BASE_URL}/login`);
    
    check(loginPageRes, {
        'login page status is 200': (r) => r.status === 200,
        'login page loads in < 500ms': (r) => r.timings.duration < 500,
    }) || errorRate.add(1);

    sleep(1);

    // Test 2: Submit Login (with valid credentials)
    let loginPayload = {
        username: 'admin',
        password: 'admin123',
        _token: 'test-token', // Laravel CSRF token
    };

    let loginHeaders = {
        'Content-Type': 'application/x-www-form-urlencoded',
    };

    let loginRes = http.post(`${BASE_URL}/login`, loginPayload, { headers: loginHeaders });
    
    check(loginRes, {
        'login submitted': (r) => r.status === 200 || r.status === 302,
        'login response time < 1s': (r) => r.timings.duration < 1000,
    }) || errorRate.add(1);

    sleep(2);

    // Test 3: Test Login with Wrong Credentials
    let wrongPayload = {
        username: 'wronguser',
        password: 'wrongpass',
        _token: 'test-token',
    };

    let wrongLoginRes = http.post(`${BASE_URL}/login`, wrongPayload, { headers: loginHeaders });
    
    check(wrongLoginRes, {
        'wrong login handled': (r) => r.status === 302 || r.status === 401,
    });

    sleep(1);
}

export function handleSummary(data) {
    return {
        'k6-tests/results/01-auth-load-test-summary.json': JSON.stringify(data),
    };
}
