import http from 'k6/http';
import { check, sleep } from 'k6';

const smoke = __ENV.SMOKE === '1';
const baseUrl = (__ENV.BASE_URL || 'https://tienda-prod.test:9443').replace(/\/$/, '');

export const options = {
  scenarios: {
    disponibilidad: smoke
      ? { executor: 'constant-vus', vus: 1, duration: '10s' }
      : {
          executor: 'ramping-vus',
          startVUs: 1,
          stages: [
            { duration: '1m', target: 5 },
            { duration: '1m', target: 15 },
            { duration: '1m', target: 30 },
            { duration: '2m', target: 50 },
            { duration: '1m', target: 0 },
          ],
          gracefulRampDown: '10s',
          gracefulStop: '10s',
        },
  },
  thresholds: {
    http_req_failed: [{ threshold: 'rate<0.01', abortOnFail: true, delayAbortEval: '30s' }],
    checks: ['rate>0.99'],
  },
};

export default function () {
  const response = http.get(`${baseUrl}/health/ready`, {
    timeout: '10s',
    tags: { name: 'Disponibilidad PHP y PostgreSQL' },
  });
  check(response, {
    'HTTP 200': (r) => r.status === 200,
    'aplicacion lista': (r) => {
      try { return r.json('status') === 'ok'; } catch (_) { return false; }
    },
  });
  sleep(smoke ? 0.5 : 0.02);
}
