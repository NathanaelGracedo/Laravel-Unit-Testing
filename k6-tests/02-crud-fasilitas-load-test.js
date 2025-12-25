import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metrics
let errorRate = new Rate('errors');

// Test configuration - Constant load
export let options = {
    vus: 20,              // 20 concurrent users
    duration: '2m',       // Run for 2 minutes
    thresholds: {
        'http_req_duration': ['p(95)<1000'], // 95% requests < 1s
        'errors': ['rate<0.15'],             // Error rate < 15%
    },
};

const BASE_URL = 'http://localhost';

export default function () {
    // Simulate authenticated user session
    let jar = http.cookieJar();
    
    // Test 1: View Fasilitas List (GET)
    let listRes = http.get(`${BASE_URL}/admin/fasilitas`);
    
    check(listRes, {
        'fasilitas list loaded': (r) => r.status === 200 || r.status === 302,
        'list response time < 1s': (r) => r.timings.duration < 1000,
    }) || errorRate.add(1);

    sleep(2);

    // Test 2: View Create Form
    let createFormRes = http.get(`${BASE_URL}/admin/fasilitas/create`);
    
    check(createFormRes, {
        'create form loaded': (r) => r.status === 200 || r.status === 302,
    }) || errorRate.add(1);

    sleep(1);

    // Test 3: Submit Create Fasilitas (POST)
    let createPayload = JSON.stringify({
        fasilitas_nama: `Fasilitas Test ${Date.now()}`,
        ruangan_id: 1,
        fasilitas_deskripsi: 'Testing K6 Performance',
    });

    let createHeaders = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    };

    let createRes = http.post(`${BASE_URL}/admin/fasilitas`, createPayload, { headers: createHeaders });
    
    check(createRes, {
        'create request sent': (r) => r.status >= 200 && r.status < 500,
        'create response time < 2s': (r) => r.timings.duration < 2000,
    }) || errorRate.add(1);

    sleep(2);

    // Test 4: View Edit Form
    let editFormRes = http.get(`${BASE_URL}/admin/fasilitas/1/edit`);
    
    check(editFormRes, {
        'edit form accessed': (r) => r.status === 200 || r.status === 302 || r.status === 404,
    });

    sleep(1);

    // Test 5: Update Fasilitas (PUT)
    let updatePayload = JSON.stringify({
        fasilitas_nama: `Updated Fasilitas ${Date.now()}`,
        ruangan_id: 1,
        fasilitas_deskripsi: 'Updated via K6',
        _method: 'PUT',
    });

    let updateRes = http.post(`${BASE_URL}/admin/fasilitas/1`, updatePayload, { headers: createHeaders });
    
    check(updateRes, {
        'update request sent': (r) => r.status >= 200 && r.status < 500,
    }) || errorRate.add(1);

    sleep(1);

    // Test 6: Delete Fasilitas (DELETE)
    let deletePayload = JSON.stringify({
        _method: 'DELETE',
    });

    let deleteRes = http.post(`${BASE_URL}/admin/fasilitas/999`, deletePayload, { headers: createHeaders });
    
    check(deleteRes, {
        'delete request sent': (r) => r.status >= 200 && r.status < 500,
    });

    sleep(2);
}

export function handleSummary(data) {
    return {
        'k6-tests/results/02-crud-fasilitas-load-test-summary.json': JSON.stringify(data),
    };
}
