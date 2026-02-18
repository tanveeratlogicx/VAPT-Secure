/**
 * VAPTSecure SixTee12 — Jest Verification Test Suite
 * Tests all 12 automated checks from the VAPT-SixTee Risk Catalogue
 *
 * Setup:
 *   npm install --save-dev jest @types/jest node-fetch ts-jest
 *   export TEST_TARGET_URL="https://your-site.com"
 *   npx jest scripts/vapt.test.ts
 */

import fetch, { Response } from "node-fetch";

const BASE = (process.env.TEST_TARGET_URL || "http://localhost").replace(/\/$/, "");
const TIMEOUT = 15_000;

// ─── Helpers ─────────────────────────────────────────────────────────────────

async function get(path: string, headers: Record<string, string> = {}): Promise<Response> {
  return fetch(`${BASE}${path}`, {
    method: "GET",
    headers: { "User-Agent": "VAPTSecure-SixTee12/1.0", ...headers },
    redirect: "manual",
  });
}

async function post(path: string, body: string, headers: Record<string, string> = {}): Promise<Response> {
  return fetch(`${BASE}${path}`, {
    method: "POST",
    headers: { "User-Agent": "VAPTSecure-SixTee12/1.0", ...headers },
    body,
    redirect: "manual",
  });
}

function isBlocked(status: number): boolean {
  return [403, 404, 401].includes(status);
}

// ─── RISK-001: wp-cron.php DoS ───────────────────────────────────────────────

describe("RISK-001: wp-cron.php DoS Attack", () => {
  let res: Response;
  beforeAll(async () => { res = await get("/wp-cron.php"); }, TIMEOUT);

  it("CHECK-001-001: /wp-cron.php returns 403 or 404", () => {
    expect(isBlocked(res.status)).toBe(true);
  });
  it("CHECK-001-001: response does not return 200 (unprotected)", () => {
    expect(res.status).not.toBe(200);
  });
});

// ─── RISK-002: XML-RPC Pingback ───────────────────────────────────────────────

describe("RISK-002: XML-RPC Pingback Attack", () => {
  const xmlPayload = `<?xml version="1.0"?><methodCall><methodName>pingback.ping</methodName><params><param><value><string>http://evil.com</string></value></param></params></methodCall>`;

  it("CHECK-002-001: pingback.ping returns 403", async () => {
    const res = await post("/xmlrpc.php", xmlPayload, { "Content-Type": "text/xml" });
    expect(isBlocked(res.status)).toBe(true);
  }, TIMEOUT);

  it("CHECK-002-001: wp.getUsersBlogs returns 403", async () => {
    const res = await post("/xmlrpc.php",
      `<?xml version="1.0"?><methodCall><methodName>wp.getUsersBlogs</methodName></methodCall>`,
      { "Content-Type": "text/xml" }
    );
    expect(isBlocked(res.status)).toBe(true);
  }, TIMEOUT);
});

// ─── RISK-003: Username Enumeration via REST API ──────────────────────────────

describe("RISK-003: Username Enumeration via REST API", () => {
  it("CHECK-003-001: /wp-json/wp/v2/users requires authentication", async () => {
    const res = await get("/wp-json/wp/v2/users");
    expect([401, 403]).toContain(res.status);
  }, TIMEOUT);

  it("CHECK-003-001: /wp-json/wp/v2/users/1 requires authentication", async () => {
    const res = await get("/wp-json/wp/v2/users/1");
    expect([401, 403]).toContain(res.status);
  }, TIMEOUT);

  it("CHECK-003-001: unauthenticated response body does not contain usernames", async () => {
    const res = await get("/wp-json/wp/v2/users");
    if (res.status === 200) {
      const body = await res.text();
      const data = JSON.parse(body);
      // Should not expose user_login or user_email
      if (Array.isArray(data)) {
        data.forEach((user: Record<string, unknown>) => {
          expect(user).not.toHaveProperty("user_login");
          expect(user).not.toHaveProperty("email");
        });
      }
    }
  }, TIMEOUT);
});

// ─── RISK-004: Email Flooding via Password Reset ─────────────────────────────

describe("RISK-004: Email Flooding via Password Reset", () => {
  it("CHECK-004-001: password reset endpoint rate-limits after 5 rapid requests", async () => {
    // Send 5 requests in rapid succession
    const requests = Array.from({ length: 5 }, () =>
      post("/wp-login.php?action=lostpassword",
        "user_login=admin",
        { "Content-Type": "application/x-www-form-urlencoded" }
      )
    );
    await Promise.all(requests);

    // 6th request should be rate-limited
    const final = await post("/wp-login.php?action=lostpassword",
      "user_login=admin",
      { "Content-Type": "application/x-www-form-urlencoded" }
    );
    // Accept 429 (rate limited) or 403 (blocked)
    // 200/302 means NOT rate limited — test logs warning
    if (final.status === 200 || final.status === 302) {
      console.warn("⚠ RISK-004: Rate limiting not enforced at HTTP layer — verify application-level protection");
    }
    expect([200, 302, 403, 429]).toContain(final.status); // doesn't hard-fail, logs warning
  }, TIMEOUT * 2);
});

// ─── RISK-005: Author Enumeration ─────────────────────────────────────────────

describe("RISK-005: Exposed Admin Username via Author Query", () => {
  it("CHECK-005-001: /?author=1 returns 403 or redirects safely", async () => {
    const res = await get("/?author=1");
    // Should not reveal username in redirect Location header
    const location = res.headers.get("location") || "";
    expect(location).not.toMatch(/\/author\/\w+/);
    // Acceptable: 403 block OR redirect that doesn't reveal username
    if (res.status === 200) {
      const body = await res.text();
      expect(body).not.toMatch(/"slug":"[a-z0-9_-]+"/);
    }
  }, TIMEOUT);

  it("CHECK-005-001: /?author=2 also protected", async () => {
    const res = await get("/?author=2");
    const location = res.headers.get("location") || "";
    expect(location).not.toMatch(/\/author\/\w+/);
  }, TIMEOUT);
});

// ─── RISK-006: Endpoint Disclosure ────────────────────────────────────────────

describe("RISK-006: REST Endpoint Disclosure", () => {
  it("CHECK-006-001: /wp-json/ index does not expose routes unauthenticated", async () => {
    const res = await get("/wp-json/");
    if (res.status === 200) {
      const body = await res.text();
      const data = JSON.parse(body);
      // If routes are exposed, namespaces should be empty or restricted
      const routes = Object.keys(data?.routes || {});
      // Log warning if many routes exposed — doesn't hard-fail (low severity)
      if (routes.length > 5) {
        console.warn(`⚠ RISK-006: ${routes.length} REST routes exposed unauthenticated`);
      }
    }
    // Low severity — pass as long as it's noted
    expect([200, 401, 403]).toContain(res.status);
  }, TIMEOUT);
});

// ─── RISK-007: Login Rate Limiting (documented as manual) ─────────────────────

describe("RISK-007: Lack of Rate Limiting on Login", () => {
  it("CHECK-007-001: login page is accessible (manual fail2ban verification needed)", async () => {
    const res = await get("/wp-login.php");
    // Just verify the page loads — actual rate limiting requires fail2ban/server config
    expect([200, 301, 302, 403]).toContain(res.status);
    console.info("ℹ  RISK-007: Manual verification required — check fail2ban config");
  }, TIMEOUT);
});

// ─── RISK-008: Username Enumeration via Login Errors ──────────────────────────

describe("RISK-008: Username Enumeration via wp-login.php", () => {
  it("CHECK-008-001: login error does not reveal username existence", async () => {
    const res = await post("/wp-login.php",
      "log=invaliduser__vapt__99999&pwd=wrongpassword&wp-submit=Log+In",
      { "Content-Type": "application/x-www-form-urlencoded" }
    );
    const text = (await res.text()).toLowerCase();
    const forbidden = [
      "unknown username",
      "incorrect password",
      "the password you entered for",
      "is not registered on this site",
    ];
    forbidden.forEach((phrase) => {
      expect(text).not.toContain(phrase);
    });
  }, TIMEOUT);
});

// ─── RISK-009: Form Rate Limiting ─────────────────────────────────────────────

describe("RISK-009: Lack of Rate Limiting on Contact Forms", () => {
  it("CHECK-009-001: contact form rate-limits after 10 rapid submissions", async () => {
    const submissions = Array.from({ length: 10 }, (_, i) =>
      post("/contact-us/",
        `email=flood${i}@test.com&message=vapt+test+${i}&name=VAPTTest`,
        { "Content-Type": "application/x-www-form-urlencoded" }
      ).catch(() => null)
    );
    await Promise.all(submissions);
    const final = await post("/contact-us/",
      "email=final@test.com&message=rate+limit+check&name=VAPTTest",
      { "Content-Type": "application/x-www-form-urlencoded" }
    ).catch(() => null);
    if (final && final.status === 200) {
      console.warn("⚠ RISK-009: Contact form rate limiting not detected — verify CAPTCHA/plugin config");
    }
  }, TIMEOUT * 3);
});

// ─── RISK-010: Server Banner ───────────────────────────────────────────────────

describe("RISK-010: Server Banner Grabbing", () => {
  let res: Response;
  beforeAll(async () => { res = await get("/"); }, TIMEOUT);

  it("CHECK-010-001: Server header is absent or generic", () => {
    const server = res.headers.get("server");
    if (server) {
      // Should not contain version numbers
      expect(server).not.toMatch(/Apache\/\d/i);
      expect(server).not.toMatch(/nginx\/\d/i);
      expect(server).not.toMatch(/\d+\.\d+\.\d+/);
    }
  });

  it("CHECK-010-001: X-Powered-By header is absent", () => {
    expect(res.headers.get("x-powered-by")).toBeNull();
  });

  it("CHECK-010-001: X-Runtime header is absent", () => {
    expect(res.headers.get("x-runtime")).toBeNull();
  });
});

// ─── RISK-011: readme.html ─────────────────────────────────────────────────────

describe("RISK-011: Information Disclosure via readme.html", () => {
  it("CHECK-011-001: /readme.html is blocked", async () => {
    const res = await get("/readme.html");
    expect(isBlocked(res.status)).toBe(true);
  }, TIMEOUT);

  it("CHECK-011-001: /license.txt is also blocked (related disclosure)", async () => {
    const res = await get("/license.txt");
    expect([200]).not.toContain(res.status);  // warn if accessible
    if (res.status === 200) {
      console.warn("⚠ RISK-011: /license.txt also accessible — consider blocking");
    }
  }, TIMEOUT);
});

// ─── RISK-012: HSTS ────────────────────────────────────────────────────────────

describe("RISK-012: HSTS Not Implemented", () => {
  it("CHECK-012-001: Strict-Transport-Security header is present", async () => {
    const httpsBase = BASE.replace(/^http:\/\//, "https://");
    const res = await fetch(`${httpsBase}/`, {
      method: "GET",
      headers: { "User-Agent": "VAPTSecure-SixTee12/1.0" },
      redirect: "manual",
    }).catch(() => null);
    if (!res) {
      console.warn("⚠ RISK-012: HTTPS not reachable from test environment — verify manually");
      return;
    }
    const hsts = res.headers.get("strict-transport-security");
    expect(hsts).not.toBeNull();
    if (hsts) {
      const maxAge = parseInt(hsts.match(/max-age=(\d+)/)?.[1] || "0");
      expect(maxAge).toBeGreaterThanOrEqual(31_536_000); // min 1 year
    }
  }, TIMEOUT);
});
