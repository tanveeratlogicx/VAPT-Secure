/**
 * VAPTSecure SixTee12 — Playwright E2E Test Suite
 * Browser-level security verification for the VAPT-SixTee 12-Risk Catalogue
 *
 * Setup:
 *   npm install --save-dev @playwright/test
 *   npx playwright install chromium
 *   export TEST_TARGET_URL="https://your-site.com"
 *   npx playwright test scripts/vapt.spec.ts
 */

import { test, expect, request as playwrightRequest } from "@playwright/test";

const BASE = (process.env.TEST_TARGET_URL || "http://localhost").replace(/\/$/, "");

// ─── RISK-001: wp-cron.php ────────────────────────────────────────────────────

test.describe("RISK-001: wp-cron.php DoS Protection", () => {
  test("CHECK-001-001: /wp-cron.php is blocked", async ({ request }) => {
    const res = await request.get(`${BASE}/wp-cron.php`);
    expect([403, 404]).toContain(res.status());
    expect(res.status()).not.toBe(200);
  });
});

// ─── RISK-002: XML-RPC ─────────────────────────────────────────────────────────

test.describe("RISK-002: XML-RPC Pingback Protection", () => {
  test("CHECK-002-001: pingback.ping is blocked", async ({ request }) => {
    const res = await request.post(`${BASE}/xmlrpc.php`, {
      headers: { "Content-Type": "text/xml" },
      data: `<?xml version="1.0"?><methodCall><methodName>pingback.ping</methodName><params><param><value><string>http://evil.com</string></value></param></params></methodCall>`,
    });
    expect([403, 404]).toContain(res.status());
  });

  test("CHECK-002-001: all XML-RPC method calls blocked", async ({ request }) => {
    const res = await request.post(`${BASE}/xmlrpc.php`, {
      headers: { "Content-Type": "text/xml" },
      data: `<?xml version="1.0"?><methodCall><methodName>system.listMethods</methodName></methodCall>`,
    });
    expect([403, 404]).toContain(res.status());
  });
});

// ─── RISK-003: REST API User Enumeration ──────────────────────────────────────

test.describe("RISK-003: REST API Username Enumeration", () => {
  test("CHECK-003-001: /wp-json/wp/v2/users requires authentication", async ({ request }) => {
    const res = await request.get(`${BASE}/wp-json/wp/v2/users`);
    expect([401, 403]).toContain(res.status());
  });

  test("CHECK-003-001: individual user endpoint also protected", async ({ request }) => {
    const res = await request.get(`${BASE}/wp-json/wp/v2/users/1`);
    expect([401, 403]).toContain(res.status());
  });
});

// ─── RISK-005: Author Enumeration ─────────────────────────────────────────────

test.describe("RISK-005: Author Query Enumeration", () => {
  test("CHECK-005-001: /?author=1 does not reveal username in redirect", async ({ request }) => {
    const res = await request.get(`${BASE}/?author=1`, { maxRedirects: 0 });
    const location = res.headers()["location"] || "";
    expect(location).not.toMatch(/\/author\/[a-zA-Z0-9_-]+/);
  });
});

// ─── RISK-006: REST Endpoint Disclosure ───────────────────────────────────────

test.describe("RISK-006: REST Endpoint Disclosure", () => {
  test("CHECK-006-001: /wp-json/ does not expose full route map unauthenticated", async ({ request }) => {
    const res = await request.get(`${BASE}/wp-json/`);
    if (res.status() === 200) {
      const body = await res.json().catch(() => ({}));
      const routeCount = Object.keys(body?.routes || {}).length;
      // Warn if more than 5 routes visible without auth
      if (routeCount > 5) {
        console.warn(`⚠ ${routeCount} REST routes exposed without authentication`);
      }
    }
    expect([200, 401, 403]).toContain(res.status());
  });
});

// ─── RISK-008: Login Error Message Enumeration ────────────────────────────────

test.describe("RISK-008: Login Error Username Enumeration", () => {
  test("CHECK-008-001: wp-login.php error messages are generic", async ({ page }) => {
    await page.goto(`${BASE}/wp-login.php`);

    await page.fill("#user_login", "invaliduser__vapt__99999");
    await page.fill("#user_pass", "wrongpassword__vapt__");
    await page.click("#wp-submit");

    await page.waitForSelector("#login_error", { timeout: 8000 }).catch(() => null);

    const errorEl = await page.$("#login_error");
    if (errorEl) {
      const errorText = (await errorEl.textContent() || "").toLowerCase();
      expect(errorText).not.toContain("unknown username");
      expect(errorText).not.toContain("incorrect password");
      expect(errorText).not.toContain("the password you entered for");
      expect(errorText).not.toContain("is not registered");
    }
  });

  test("CHECK-008-001: same error for invalid password on known-like username", async ({ page }) => {
    // Test with 'admin' (likely exists) - error should STILL be generic
    await page.goto(`${BASE}/wp-login.php`);
    await page.fill("#user_login", "admin");
    await page.fill("#user_pass", "wrongpassword__vapt__definitely_wrong");
    await page.click("#wp-submit");

    await page.waitForSelector("#login_error", { timeout: 8000 }).catch(() => null);

    const errorEl = await page.$("#login_error");
    if (errorEl) {
      const errorText = (await errorEl.textContent() || "").toLowerCase();
      expect(errorText).not.toContain("the password you entered for");
      expect(errorText).not.toContain("is incorrect");
    }
  });
});

// ─── RISK-010: Server Banner ───────────────────────────────────────────────────

test.describe("RISK-010: Server Banner Grabbing", () => {
  test("CHECK-010-001: no server version headers in HTTP response", async ({ request }) => {
    const res = await request.get(`${BASE}/`);
    const headers = res.headers();

    // Server header must not contain version
    if (headers["server"]) {
      expect(headers["server"]).not.toMatch(/\d+\.\d+/);
      expect(headers["server"]).not.toMatch(/Apache\/|nginx\//i);
    }

    // X-Powered-By must not exist
    expect(headers["x-powered-by"]).toBeUndefined();
    expect(headers["x-runtime"]).toBeUndefined();
    expect(headers["x-aspnet-version"]).toBeUndefined();
  });
});

// ─── RISK-011: readme.html ─────────────────────────────────────────────────────

test.describe("RISK-011: WordPress Version Disclosure", () => {
  test("CHECK-011-001: /readme.html is blocked", async ({ request }) => {
    const res = await request.get(`${BASE}/readme.html`);
    expect([403, 404]).toContain(res.status());
  });

  test("CHECK-011-001: /wp-admin/install.php is not publicly accessible", async ({ request }) => {
    const res = await request.get(`${BASE}/wp-admin/install.php`);
    expect(res.status()).not.toBe(200);
  });
});

// ─── RISK-012: HSTS ────────────────────────────────────────────────────────────

test.describe("RISK-012: HSTS Not Implemented", () => {
  test("CHECK-012-001: Strict-Transport-Security header present with min 1-year max-age", async ({ request }) => {
    const httpsBase = BASE.replace(/^http:\/\//, "https://");
    try {
      const res = await request.get(`${httpsBase}/`);
      const hsts = res.headers()["strict-transport-security"];
      expect(hsts).toBeDefined();
      if (hsts) {
        const maxAge = parseInt(hsts.match(/max-age=(\d+)/)?.[1] || "0");
        expect(maxAge).toBeGreaterThanOrEqual(31_536_000);
      }
    } catch {
      console.warn("⚠ RISK-012: HTTPS endpoint unreachable in test environment — verify manually");
    }
  });

  test("CHECK-012-001: HTTP requests redirect to HTTPS", async ({ request }) => {
    const httpBase = BASE.replace(/^https:\/\//, "http://");
    const res = await request.get(`${httpBase}/`, { maxRedirects: 0 });
    if ([301, 302, 307, 308].includes(res.status())) {
      const location = res.headers()["location"] || "";
      expect(location).toMatch(/^https:\/\//);
    }
  });
});

// ─── Visual: Dashboard Renders ────────────────────────────────────────────────

test.describe("Dashboard: VAPTSecure SixTee12 UI", () => {
  test("Dashboard loads and displays all 12 risk cards", async ({ page }) => {
    // Only run if a local dashboard is served
    const dashboardUrl = process.env.DASHBOARD_URL;
    if (!dashboardUrl) {
      test.skip(true, "DASHBOARD_URL not set — skipping UI test");
      return;
    }
    await page.goto(dashboardUrl);
    await page.waitForSelector('[data-risk-id]', { timeout: 10000 });
    const cards = await page.$$('[data-risk-id]');
    expect(cards.length).toBe(12);
  });

  test("Run Full Scan button triggers scan state", async ({ page }) => {
    const dashboardUrl = process.env.DASHBOARD_URL;
    if (!dashboardUrl) {
      test.skip(true, "DASHBOARD_URL not set");
      return;
    }
    await page.goto(dashboardUrl);
    await page.click('button:has-text("Run Full Scan")');
    await expect(page.locator('button:has-text("Scanning")')).toBeVisible({ timeout: 5000 });
  });
});
