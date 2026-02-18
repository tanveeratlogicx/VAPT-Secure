# 🛡 VAPTSecure SixTee12

> **Google Antigravity Skill** — Situation-Aware, Production-Ready Interfaces
> and Verification Test Suites for the WordPress VAPT 12-Risk Catalogue.

```
Schema:  v3.4.0          OWASP:   Top 10 2025
Risks:   12              Plugin:  WordPress VAPT Protection Suite
Phase:   3               Status:  Enterprise-Ready ✦
```

---

## What Is This?

**VAPTSecure SixTee12** is an AI skill file that instructs Claude to generate
two classes of output from the VAPT-SixTee 12-Risk Catalogue:

| Output Class | What It Produces |
|---|---|
| **Situation-Aware Interfaces** | React dashboards, risk cards, scan UIs, live status widgets |
| **Verification Test Suites** | Bash smoke tests, Jest unit tests, Playwright E2E, CI/CD YAML |

The 12 risks span WordPress's most critical attack surfaces — DoS, DDoS
amplification, username enumeration, brute-force, information disclosure, MITM,
and API exposure — all mapped to **OWASP Top 10 2025** and **CVSS v3.1**.

---

## Folder Structure

```
VAPTSecure-SixTee12/
├── SKILL.md                          ← Core skill definition (load this)
├── README.md                         ← You are here
├── HOW-TO-USE.md                     ← Step-by-step usage guide
│
├── scripts/
│   ├── vapt-smoke.sh                 ← Bash curl smoke tests (all 12 checks)
│   ├── vapt.test.ts                  ← Jest unit test suite
│   ├── vapt.spec.ts                  ← Playwright E2E test suite
│   └── vapt-ci.yml                   ← GitHub Actions CI/CD pipeline
│
├── examples/
│   ├── risk-card.html                ← Standalone risk card UI (no build needed)
│   ├── api-scan-call.ts              ← Example API scan integration
│   ├── remediation-output.json       ← Example remediation report output
│   └── dashboard-usage.tsx           ← Full dashboard wiring example
│
└── resources/
    ├── risk-catalogue.json           ← Canonical 12-risk data (lightweight)
    ├── owasp-mappings.md             ← OWASP 2025 → Risk cross-reference
    ├── cvss-reference.md             ← CVSS scoring quick reference
    └── remediation-checklist.md      ← Printable remediation checklist
```

---

## The 12 Risks at a Glance

| ID | Title | Severity | CVSS | OWASP 2025 |
|---|---|---|---|---|
| RISK-001 | wp-cron.php DoS Attack | 🔴 High | 7.5 | A06 - Insecure Design |
| RISK-002 | XML-RPC Pingback DDoS | 🔴 High | 7.5 | A02 - Security Misconfiguration |
| RISK-003 | Username Enum via REST API | 🟡 Medium | 5.3 | A01 - Broken Access Control |
| RISK-004 | Email Flooding via Password Reset | 🟡 Medium | 5.3 | A07 - Auth Failures |
| RISK-005 | Admin Username via Author Query | 🟡 Medium | 5.3 | A01 - Broken Access Control |
| RISK-006 | REST Endpoint Disclosure | 🟢 Low | 3.7 | A02 - Security Misconfiguration |
| RISK-007 | No Rate Limiting on Login | 🔴 High | 7.5 | A07 - Auth Failures |
| RISK-008 | Username Enum via Login Errors | 🟡 Medium | 5.3 | A07 - Auth Failures |
| RISK-009 | No Rate Limiting on Forms | 🟡 Medium | 5.3 | A06 - Insecure Design |
| RISK-010 | Server Banner Grabbing | 🟢 Low | 3.7 | A02 - Security Misconfiguration |
| RISK-011 | WP Version via readme.html | 🟡 Medium | 5.3 | A02 - Security Misconfiguration |
| RISK-012 | HSTS Not Implemented | 🟡 Medium | 5.3 | A02 - Security Misconfiguration |

**Total Max CVSS: 71.9** — Security Score = `100 - (unprotected_cvss / 71.9 × 100)`

---

## Quick Start

```bash
# 1. Clone / copy this skill folder into your project
cp -r VAPTSecure-SixTee12/ ./skills/

# 2. Run the smoke test against your WordPress site
export TEST_TARGET_URL="https://your-wordpress-site.com"
bash skills/VAPTSecure-SixTee12/scripts/vapt-smoke.sh

# 3. Run Jest unit tests
cd your-project && npm test -- --testPathPattern=vapt

# 4. Run Playwright E2E tests
npx playwright test skills/VAPTSecure-SixTee12/scripts/vapt.spec.ts
```

---

## Prompting Claude with This Skill

Load `SKILL.md` into Claude's context, then use prompts like:

```
"Using the VAPTSecure SixTee12 skill, generate a production React dashboard
 for all 12 risks filtered to show only HIGH severity unprotected items."

"Using the VAPTSecure SixTee12 skill, generate a full Playwright test suite
 for RISK-003, RISK-005, and RISK-007."

"Using the VAPTSecure SixTee12 skill, generate a remediation report for a
 site where RISK-002, RISK-007, and RISK-012 are currently unprotected."
```

See `HOW-TO-USE.md` for the complete prompting guide.

---

## Compliance Coverage

| Standard | Coverage |
|---|---|
| OWASP Top 10 2025 | A01, A02, A06, A07 |
| PCI-DSS | Requirement 6.5 |
| GDPR | Article 32 |
| NIST CSF | PR.DS-5 |
| CWE | 400, 918, 204, 770, 307, 203, 200, 319 |

---

## License

See `LICENSE.txt`. Enterprise-ready. Production-validated `2026-02-16`.
