import http from 'k6/http';
import { check, group, sleep } from 'k6';
import exec from 'k6/execution';
import { Counter, Rate, Trend } from 'k6/metrics';

const LOAD_PROFILES = {
    smoke: {
        duration: '1m',
        preAllocatedVUs: 10,
        maxVUs: 50,
        healthRate: 2,
        reportRate: 1,
        trackingRate: 1,
        webhookRate: 1,
        maxHttpErrorRate: 0.05,
        minChecksRate: 0.95,
        maxP95Ms: 2000,
        maxP99Ms: 3500,
        maxBusinessFailureRate: 0.15,
    },
    baseline: {
        duration: '5m',
        preAllocatedVUs: 40,
        maxVUs: 200,
        healthRate: 12,
        reportRate: 5,
        trackingRate: 3,
        webhookRate: 12,
        maxHttpErrorRate: 0.02,
        minChecksRate: 0.98,
        maxP95Ms: 1500,
        maxP99Ms: 2500,
        maxBusinessFailureRate: 0.1,
    },
    stress: {
        duration: '10m',
        preAllocatedVUs: 80,
        maxVUs: 400,
        healthRate: 20,
        reportRate: 12,
        trackingRate: 8,
        webhookRate: 24,
        maxHttpErrorRate: 0.03,
        minChecksRate: 0.97,
        maxP95Ms: 2200,
        maxP99Ms: 3500,
        maxBusinessFailureRate: 0.15,
    },
    soak: {
        duration: '30m',
        preAllocatedVUs: 40,
        maxVUs: 200,
        healthRate: 8,
        reportRate: 3,
        trackingRate: 2,
        webhookRate: 10,
        maxHttpErrorRate: 0.02,
        minChecksRate: 0.98,
        maxP95Ms: 1700,
        maxP99Ms: 2800,
        maxBusinessFailureRate: 0.1,
    },
};

const profileName = String(__ENV.K6_PROFILE || 'baseline').toLowerCase();
const profile = LOAD_PROFILES[profileName] || LOAD_PROFILES.baseline;

const baseUrl = trimTrailingSlashes(__ENV.BASE_URL || 'http://127.0.0.1:8000');
const basePath = normalizeBasePath(__ENV.BASE_PATH || '');
const requestTimeout = __ENV.REQUEST_TIMEOUT || '30s';

const webhookToken = String(__ENV.WEBHOOK_TOKEN || '');
const webhookFixedSender = String(__ENV.WEBHOOK_FIXED_SENDER || '6281234567890');
const webhookUseFixedSender = envBool('WEBHOOK_USE_FIXED_SENDER', false);
const webhookAllowedStatuses = parseStatusSet(__ENV.WEBHOOK_ALLOWED_STATUSES || '200,401,422,429');
const webhookMessages = parseList(
    __ENV.WEBHOOK_MESSAGES || 'LAPOR,Jalan rusak di depan balai desa,2,Lampu jalan mati sejak kemarin'
);

const reportAllowedStatuses = parseStatusSet(__ENV.REPORT_ALLOWED_STATUSES || '302,303');
const otpRequestAllowedStatuses = parseStatusSet(__ENV.OTP_REQUEST_ALLOWED_STATUSES || '302,303');
const otpVerifyAllowedStatuses = parseStatusSet(__ENV.OTP_VERIFY_ALLOWED_STATUSES || '302,303');

const expectedStatus200 = expectedStatusesFromSet(new Set([200]), [200]);
const expectedReportSubmitStatuses = expectedStatusesFromSet(reportAllowedStatuses, [302, 303]);
const expectedOtpRequestStatuses = expectedStatusesFromSet(otpRequestAllowedStatuses, [302, 303]);
const expectedOtpVerifyStatuses = expectedStatusesFromSet(otpVerifyAllowedStatuses, [302, 303]);
const expectedWebhookStatuses = expectedStatusesFromSet(webhookAllowedStatuses, [200, 401, 422, 429]);

const otpCode = String(__ENV.OTP_CODE || '000000');
const enableOtpVerify = envBool('ENABLE_OTP_VERIFY', true);

const enableHealthScenario = envBool('ENABLE_HEALTH_SCENARIO', true);
const enableReportScenario = envBool('ENABLE_REPORT_SCENARIO', true);
const enableTrackingScenario = envBool('ENABLE_TRACKING_SCENARIO', true);
const enableWebhookScenario = envBool('ENABLE_WEBHOOK_SCENARIO', true);

const preAllocatedVUs = envInt('PREALLOCATED_VUS', profile.preAllocatedVUs);
const maxVUs = envInt('MAX_VUS', profile.maxVUs);
const duration = __ENV.K6_DURATION || profile.duration;

const healthRate = envInt('HEALTH_RPS', profile.healthRate);
const reportRate = envInt('REPORT_RPS', profile.reportRate);
const trackingRate = envInt('TRACKING_RPS', profile.trackingRate);
const webhookRate = envInt('WEBHOOK_RPS', profile.webhookRate);

const maxHttpErrorRate = envFloat('MAX_HTTP_ERROR_RATE', profile.maxHttpErrorRate);
const minChecksRate = envFloat('MIN_CHECKS_RATE', profile.minChecksRate);
const maxP95Ms = envInt('MAX_P95_MS', profile.maxP95Ms);
const maxP99Ms = envInt('MAX_P99_MS', profile.maxP99Ms);
const maxBusinessFailureRate = envFloat('MAX_BUSINESS_FAILURE_RATE', profile.maxBusinessFailureRate);

const scenarios = {};

if (enableHealthScenario) {
    scenarios.health = {
        executor: 'constant-arrival-rate',
        exec: 'healthScenario',
        rate: healthRate,
        timeUnit: '1s',
        duration,
        preAllocatedVUs,
        maxVUs,
        tags: { scenario: 'health' },
    };
}

if (enableReportScenario) {
    scenarios.report_submission = {
        executor: 'constant-arrival-rate',
        exec: 'reportScenario',
        rate: reportRate,
        timeUnit: '1s',
        duration,
        preAllocatedVUs,
        maxVUs,
        tags: { scenario: 'report_submission' },
    };
}

if (enableTrackingScenario) {
    scenarios.tracking_otp = {
        executor: 'constant-arrival-rate',
        exec: 'trackingScenario',
        rate: trackingRate,
        timeUnit: '1s',
        duration,
        preAllocatedVUs,
        maxVUs,
        tags: { scenario: 'tracking_otp' },
    };
}

if (enableWebhookScenario) {
    scenarios.webhook = {
        executor: 'constant-arrival-rate',
        exec: 'webhookScenario',
        rate: webhookRate,
        timeUnit: '1s',
        duration,
        preAllocatedVUs,
        maxVUs,
        tags: { scenario: 'webhook' },
    };
}

if (Object.keys(scenarios).length === 0) {
    throw new Error('No scenario enabled. Set at least one ENABLE_*_SCENARIO=true.');
}

export const options = {
    scenarios,
    thresholds: {
        checks: [`rate>${minChecksRate}`],
        http_req_failed: [`rate<${maxHttpErrorRate}`],
        http_req_duration: [`p(95)<${maxP95Ms}`, `p(99)<${maxP99Ms}`],
        business_failures: [`rate<${maxBusinessFailureRate}`],
    },
    summaryTrendStats: ['avg', 'min', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
    insecureSkipTLSVerify: envBool('INSECURE_SKIP_TLS_VERIFY', false),
};

const reportsSubmitted = new Counter('reports_submitted');
const trackingOtpRequests = new Counter('tracking_otp_requests');
const webhookCalls = new Counter('webhook_calls');
const businessFailures = new Rate('business_failures');
const healthFlowDuration = new Trend('health_flow_duration', true);
const reportFlowDuration = new Trend('report_flow_duration', true);
const trackingFlowDuration = new Trend('tracking_flow_duration', true);
const webhookFlowDuration = new Trend('webhook_flow_duration', true);

export function healthScenario() {
    const startedAt = Date.now();
    let success = true;

    group('health', () => {
        const upResponse = http.get(buildUrl('/up'), htmlParams('GET /up', expectedStatus200));
        const upOk = check(upResponse, {
            'GET /up is 200': (response) => response.status === 200,
        });

        const homeResponse = http.get(buildUrl('/'), htmlParams('GET /', expectedStatus200));
        const homeOk = check(homeResponse, {
            'GET / is 200': (response) => response.status === 200,
        });

        const reportPageResponse = http.get(buildUrl('/lapor'), htmlParams('GET /lapor', expectedStatus200));
        const reportPageOk = check(reportPageResponse, {
            'GET /lapor is 200': (response) => response.status === 200,
            'GET /lapor has csrf token': (response) => extractCsrfToken(response.body) !== null,
        });

        success = upOk && homeOk && reportPageOk;
    });

    healthFlowDuration.add(Date.now() - startedAt);
    businessFailures.add(!success);
    sleep(randomBetween(0.1, 0.5));
}

export function reportScenario() {
    const startedAt = Date.now();
    const created = createReport();
    const success = created.ok;

    if (success) {
        reportsSubmitted.add(1);
    }

    reportFlowDuration.add(Date.now() - startedAt);
    businessFailures.add(!success);
    sleep(randomBetween(0.2, 0.8));
}

export function trackingScenario() {
    const startedAt = Date.now();
    let success = false;

    const created = createReport();
    if (created.ok) {
        const otpRequested = requestTrackingOtp(created.ticket, created.phone);

        if (otpRequested) {
            trackingOtpRequests.add(1);

            if (enableOtpVerify) {
                success = verifyTrackingOtp(created.ticket, created.phone);
            } else {
                success = true;
            }
        }
    }

    trackingFlowDuration.add(Date.now() - startedAt);
    businessFailures.add(!success);
    sleep(randomBetween(0.2, 0.8));
}

export function webhookScenario() {
    const startedAt = Date.now();
    webhookCalls.add(1);

    const sender = webhookUseFixedSender ? webhookFixedSender : randomWebhookSender();
    const message = randomFrom(webhookMessages);
    const payload = JSON.stringify({
        sender,
        message,
        name: `k6-${exec.vu.idInTest}`,
    });

    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    };

    if (webhookToken !== '') {
        headers['X-Fonnte-Token'] = webhookToken;
    }

    const response = http.post(
        buildUrl('/webhook/fonnte'),
        payload,
        jsonParams('POST /webhook/fonnte', headers, expectedWebhookStatuses)
    );

    const success = check(response, {
        'POST /webhook/fonnte allowed status': (result) => webhookAllowedStatuses.has(result.status),
    });

    webhookFlowDuration.add(Date.now() - startedAt);
    businessFailures.add(!success);
    sleep(randomBetween(0.05, 0.25));
}

function createReport() {
    const phone = randomLocalPhone();
    const pageResponse = http.get(buildUrl('/lapor'), htmlParams('GET /lapor for submit', expectedStatus200));

    const pageOk = check(pageResponse, {
        'submit precheck /lapor status is 200': (response) => response.status === 200,
    });

    if (!pageOk) {
        return { ok: false, reason: `lapor_page_${pageResponse.status}` };
    }

    const csrfToken = extractCsrfToken(pageResponse.body);
    if (!csrfToken) {
        return { ok: false, reason: 'csrf_missing_on_lapor' };
    }

    const payload = {
        _token: csrfToken,
        title: buildReportTitle(),
        description: buildReportDescription(),
        phone,
        location_name: buildLocationName(),
        latitude: randomLatitude(),
        longitude: randomLongitude(),
    };

    const submitResponse = http.post(
        buildUrl('/lapor'),
        payload,
        formParams('/lapor', 'POST /lapor', expectedReportSubmitStatuses)
    );

    const statusOk = check(submitResponse, {
        'POST /lapor allowed status': (response) => reportAllowedStatuses.has(response.status),
    });

    if (!statusOk) {
        return { ok: false, reason: `lapor_submit_${submitResponse.status}` };
    }

    const location = getHeader(submitResponse.headers, 'Location');
    const ticket = extractTicketFromLocation(location);

    if (!ticket) {
        return { ok: false, reason: 'ticket_missing_after_submit' };
    }

    return {
        ok: true,
        ticket,
        phone,
    };
}

function requestTrackingOtp(ticket, phone) {
    const trackingPageResponse = http.get(
        buildUrl(`/tracking?ticket=${encodeURIComponent(ticket)}`),
        htmlParams('GET /tracking', expectedStatus200)
    );

    const trackingPageOk = check(trackingPageResponse, {
        'GET /tracking status is 200': (response) => response.status === 200,
    });

    if (!trackingPageOk) {
        return false;
    }

    const csrfToken = extractCsrfToken(trackingPageResponse.body);
    if (!csrfToken) {
        return false;
    }

    const response = http.post(
        buildUrl('/tracking/request-otp'),
        {
            _token: csrfToken,
            ticket,
            phone,
        },
        formParams(
            `/tracking?ticket=${encodeURIComponent(ticket)}`,
            'POST /tracking/request-otp',
            expectedOtpRequestStatuses
        )
    );

    return check(response, {
        'POST /tracking/request-otp allowed status': (result) => otpRequestAllowedStatuses.has(result.status),
    });
}

function verifyTrackingOtp(ticket, phone) {
    const trackingPageResponse = http.get(
        buildUrl(`/tracking?ticket=${encodeURIComponent(ticket)}`),
        htmlParams('GET /tracking for verify', expectedStatus200)
    );

    const trackingPageOk = check(trackingPageResponse, {
        'GET /tracking for verify status is 200': (response) => response.status === 200,
    });

    if (!trackingPageOk) {
        return false;
    }

    const csrfToken = extractCsrfToken(trackingPageResponse.body);
    if (!csrfToken) {
        return false;
    }

    const response = http.post(
        buildUrl('/tracking/verify-otp'),
        {
            _token: csrfToken,
            ticket,
            phone,
            otp: otpCode,
        },
        formParams(
            `/tracking?ticket=${encodeURIComponent(ticket)}`,
            'POST /tracking/verify-otp',
            expectedOtpVerifyStatuses
        )
    );

    return check(response, {
        'POST /tracking/verify-otp allowed status': (result) => otpVerifyAllowedStatuses.has(result.status),
    });
}

function htmlParams(name, responseCallback = null) {
    return {
        timeout: requestTimeout,
        redirects: 0,
        tags: { name },
        responseCallback,
        headers: {
            Accept: 'text/html,application/xhtml+xml',
            'User-Agent': 'k6-stability-test',
        },
    };
}

function formParams(refererPath, name, responseCallback = null) {
    return {
        timeout: requestTimeout,
        redirects: 0,
        tags: { name },
        responseCallback,
        headers: {
            Accept: 'text/html,application/xhtml+xml',
            Referer: buildUrl(refererPath),
            'User-Agent': 'k6-stability-test',
        },
    };
}

function jsonParams(name, headers = {}, responseCallback = null) {
    return {
        timeout: requestTimeout,
        redirects: 0,
        tags: { name },
        responseCallback,
        headers: {
            'User-Agent': 'k6-stability-test',
            ...headers,
        },
    };
}

function buildUrl(path) {
    const normalizedPath = path.startsWith('/') ? path : `/${path}`;

    return `${baseUrl}${basePath}${normalizedPath}`;
}

function extractCsrfToken(html) {
    if (typeof html !== 'string' || html === '') {
        return null;
    }

    const tokenPatterns = [
        /name="_token"\s+value="([^"]+)"/i,
        /value="([^"]+)"\s+name="_token"/i,
        /name='_token'\s+value='([^']+)'/i,
        /value='([^']+)'\s+name='_token'/i,
    ];

    for (const pattern of tokenPatterns) {
        const match = html.match(pattern);
        if (match && match[1]) {
            return match[1];
        }
    }

    return null;
}

function extractTicketFromLocation(locationHeader) {
    if (!locationHeader) {
        return null;
    }

    const locationValue = Array.isArray(locationHeader) ? locationHeader[0] : String(locationHeader);
    const ticketMatch = locationValue.match(/[?&]ticket=([^&]+)/i);

    if (!ticketMatch || !ticketMatch[1]) {
        return null;
    }

    return decodeURIComponent(ticketMatch[1]);
}

function getHeader(headers, name) {
    if (!headers || typeof headers !== 'object') {
        return null;
    }

    const direct = headers[name];
    if (direct) {
        return Array.isArray(direct) ? direct[0] : direct;
    }

    const lower = headers[name.toLowerCase()];
    if (lower) {
        return Array.isArray(lower) ? lower[0] : lower;
    }

    const upper = headers[name.toUpperCase()];
    if (upper) {
        return Array.isArray(upper) ? upper[0] : upper;
    }

    return null;
}

function buildReportTitle() {
    return `Laporan beban awal ${exec.vu.idInTest}-${exec.scenario.iterationInInstance}`;
}

function buildReportDescription() {
    return `Ini payload uji stabilitas dari k6 pada ${new Date().toISOString()}. Detail minimal sepuluh karakter.`;
}

function buildLocationName() {
    return `Lokasi Uji ${exec.vu.idInTest}-${exec.scenario.iterationInInstance}`;
}

function randomLatitude() {
    return (Math.random() * 180 - 90).toFixed(6);
}

function randomLongitude() {
    return (Math.random() * 360 - 180).toFixed(6);
}

function randomLocalPhone() {
    return `08${randomDigits(10)}`;
}

function randomWebhookSender() {
    return `628${randomDigits(9)}`;
}

function randomDigits(length) {
    let output = '';

    for (let index = 0; index < length; index += 1) {
        output += String(Math.floor(Math.random() * 10));
    }

    return output;
}

function randomFrom(items) {
    if (!Array.isArray(items) || items.length === 0) {
        return '';
    }

    const index = Math.floor(Math.random() * items.length);

    return items[index];
}

function randomBetween(min, max) {
    return min + Math.random() * (max - min);
}

function parseList(value) {
    return String(value)
        .split(',')
        .map((entry) => entry.trim())
        .filter((entry) => entry !== '');
}

function parseStatusSet(raw) {
    const statuses = parseList(raw)
        .map((entry) => Number.parseInt(entry, 10))
        .filter((value) => Number.isFinite(value));

    return new Set(statuses);
}

function expectedStatusesFromSet(statusSet, fallbackStatuses = [200]) {
    const statuses = Array.from(statusSet).filter((statusCode) => Number.isInteger(statusCode));
    const effectiveStatuses = statuses.length > 0 ? statuses : fallbackStatuses;

    return http.expectedStatuses(...effectiveStatuses);
}

function envInt(name, fallback) {
    const raw = __ENV[name];
    if (raw === undefined || raw === null || raw === '') {
        return fallback;
    }

    const parsed = Number.parseInt(raw, 10);

    return Number.isFinite(parsed) ? parsed : fallback;
}

function envFloat(name, fallback) {
    const raw = __ENV[name];
    if (raw === undefined || raw === null || raw === '') {
        return fallback;
    }

    const parsed = Number.parseFloat(raw);

    return Number.isFinite(parsed) ? parsed : fallback;
}

function envBool(name, fallback) {
    const raw = __ENV[name];
    if (raw === undefined || raw === null || raw === '') {
        return fallback;
    }

    return ['1', 'true', 'yes', 'on'].includes(String(raw).toLowerCase());
}

function trimTrailingSlashes(value) {
    return String(value).replace(/\/+$/, '');
}

function normalizeBasePath(value) {
    const clean = String(value).trim();

    if (clean === '' || clean === '/') {
        return '';
    }

    return `/${clean.replace(/^\/+|\/+$/g, '')}`;
}

export function handleSummary(data) {
    const checks = data.metrics.checks || {};
    const checksValues = checks.values || {};
    const checksPassed = checksValues.passes ?? checks.passes ?? 0;
    const checksFailed = checksValues.fails ?? checks.fails ?? 0;
    const checksTotal = checksPassed + checksFailed;
    const checksRate = checksTotal > 0 ? (checksPassed / checksTotal) * 100 : 0;

    const lines = [
        '=== K6 Stability Summary ===',
        `Profile             : ${profileName}`,
        `Base URL            : ${baseUrl}${basePath}`,
        `Checks              : ${checksRate.toFixed(2)}% (${checksPassed} passed / ${checksFailed} failed)`,
        `HTTP Failed Rate    : ${formatRate(data, 'http_req_failed')}`,
        `HTTP Req P95 (ms)   : ${formatTrend(data, 'http_req_duration', 'p(95)')}`,
        `HTTP Req P99 (ms)   : ${formatTrend(data, 'http_req_duration', 'p(99)')}`,
        `Business Fail Rate  : ${formatRate(data, 'business_failures')}`,
        `Reports Submitted   : ${formatCounter(data, 'reports_submitted')}`,
        `OTP Requests        : ${formatCounter(data, 'tracking_otp_requests')}`,
        `Webhook Calls       : ${formatCounter(data, 'webhook_calls')}`,
    ];

    const output = {
        stdout: `${lines.join('\n')}\n`,
    };

    const summaryJson = String(__ENV.SUMMARY_JSON || '').trim();
    if (summaryJson !== '') {
        output[summaryJson] = JSON.stringify(data, null, 2);
    }

    return output;
}

function formatRate(data, metricName) {
    const metric = data.metrics[metricName];
    if (!metric || !metric.values) {
        return 'n/a';
    }

    const rate = metric.values.rate;
    if (rate === undefined) {
        return 'n/a';
    }

    return `${(rate * 100).toFixed(2)}%`;
}

function formatTrend(data, metricName, statName) {
    const metric = data.metrics[metricName];
    if (!metric || !metric.values) {
        return 'n/a';
    }

    const value = metric.values[statName];
    if (value === undefined) {
        return 'n/a';
    }

    return `${Number(value).toFixed(2)}`;
}

function formatCounter(data, metricName) {
    const metric = data.metrics[metricName];
    if (!metric || !metric.values) {
        return '0';
    }

    const count = metric.values.count;
    if (count === undefined) {
        return '0';
    }

    return String(Math.round(Number(count)));
}
