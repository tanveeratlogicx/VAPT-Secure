# HOW TO USE — VAPTSecure SixTee12

## Table of Contents

1. [Setup](#1-setup)
2. [Loading the Skill into Claude](#2-loading-the-skill-into-claude)
3. [Generating Interfaces](#3-generating-interfaces)
4. [Running Verification Tests](#4-running-verification-tests)
5. [CI/CD Integration](#5-cicd-integration)
6. [Reading the Risk Score](#6-reading-the-risk-score)
7. [Prompt Reference](#7-prompt-reference)
8. [Troubleshooting](#8-troubleshooting)

---

## 1. Setup

### Requirements

| Tool | Version | Purpose |
|---|---|---|
| Node.js | ≥ 18 | Jest + Playwright tests |
| bash | any | Smoke test script |
| curl | any | HTTP checks |
| npm / pnpm | any | Package management |
| Claude API / claude.ai | — | AI skill execution |

### Install test dependencies

```bash
npm install --save-dev jest @types/jest node-fetch ts-jest typescript
npm install --save-dev @playwright/test
npx playwright install chromium
```

### Configure environment

```bash
# .env or shell export
export TEST_TARGET_URL="https://your-wordpress-site.com"

# Optional: for authenticated REST API tests
export WP_AUTH_TOKEN="Bearer your_application_password_here"
```

---

## 2. Loading the Skill into Claude

### Method A — Claude.ai (Web/App)

1. Open a new conversation at **claude.ai**
2. Click the **paperclip / attachment** icon
3. Upload `SKILL.md` from this folder
4. Start your prompt — Claude will use the skill automatically

### Method B — Anthropic API

```typescript
import Anthropic from "@anthropic-ai/sdk";
import fs from "fs";

const client = new Anthropic();
const skill = fs.readFileSync("./VAPTSecure-SixTee12/SKILL.md", "utf-8");

const response = await client.messages.create({
  model: "claude-sonnet-4-6",
  max_tokens: 8192,
  system: `You are a security engineering assistant. 
The following skill defines your behaviour for VAPT risk outputs:

${skill}`,
  messages: [
    {
      role: "user",
      content: "Generate a production React dashboard for all 12 risks.",
    },
  ],
});
```

### Method C — Claude Code (CLI)

```bash
# Pass skill as a project file
claude --system-file VAPTSecure-SixTee12/SKILL.md \
  "Generate the full Jest test suite for all 12 risks"
```

---

## 3. Generating Interfaces

### Full Dashboard (All 12 Risks)

```
Prompt:
  Using the VAPTSecure SixTee12 skill, generate a production-ready
  React dashboard for all 12 VAPT risks. Apply the Google Antigravity
  aesthetic. Include live scan simulation, severity filters, and
  a dynamic security score ring.
```

Expected output: A `.jsx` file ready to drop into any React project.

---

### Filtered Dashboard (Severity or Category)

```
Prompt:
  Using VAPTSecure SixTee12, generate a React interface showing only
  HIGH severity risks (RISK-001, RISK-002, RISK-007). Include
  expanded remediation steps and a "Mark as Protected" toggle.
```

---

### Single Risk Card Component

```
Prompt:
  Using VAPTSecure SixTee12, generate a standalone React component
  for RISK-007 (Lack of Rate Limiting on Login). Show: severity badge,
  CVSS arc, attack scenario, test payloads, and remediation panel.
```

---

### WordPress Admin Panel Widget

```
Prompt:
  Using VAPTSecure SixTee12, generate a WordPress admin dashboard
  widget (HTML + vanilla JS, no React) that displays the protection
  status of all 12 risks with colour-coded severity indicators.
```

---

### Remediation Report (PDF-style HTML)

```
Prompt:
  Using VAPTSecure SixTee12, generate a printable HTML remediation
  report for a site where RISK-002, RISK-007, and RISK-012 are
  currently UNPROTECTED. Include executive summary, risk details,
  step-by-step fix instructions, and compliance impact.
```

---

## 4. Running Verification Tests

### Bash Smoke Tests (Fastest — no install needed)

```bash
export TEST_TARGET_URL="https://your-site.com"
bash scripts/vapt-smoke.sh
```

Expected output:
```
✅  CHECK-001-001  wp-cron.php blocked
✅  CHECK-002-001  XML-RPC blocked
❌  CHECK-003-001  REST users requires auth
✅  CHECK-004-001  Password reset rate limited
...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  VAPT Results: ✅ 10 passed  ❌ 2 failed
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

Exit code `0` = all pass. Exit code `1` = one or more failures.

---

### Jest Unit Tests

```bash
# Run all VAPT tests
TEST_TARGET_URL=https://your-site.com npx jest scripts/vapt.test.ts

# Run a specific risk
TEST_TARGET_URL=https://your-site.com npx jest --testNamePattern="RISK-007"

# With coverage
npx jest scripts/vapt.test.ts --coverage
```

---

### Playwright E2E Tests

```bash
# Run all E2E checks
TEST_TARGET_URL=https://your-site.com npx playwright test scripts/vapt.spec.ts

# Run headed (watch the browser)
npx playwright test scripts/vapt.spec.ts --headed

# Run a single test
npx playwright test scripts/vapt.spec.ts --grep "RISK-008"

# Generate HTML report
npx playwright test scripts/vapt.spec.ts --reporter=html
npx playwright show-report
```

---

### Run All Three Layers

```bash
#!/usr/bin/env bash
set -e
URL="https://your-site.com"

echo "=== Layer 1: Smoke Tests ==="
TEST_TARGET_URL=$URL bash scripts/vapt-smoke.sh

echo "=== Layer 2: Jest Unit Tests ==="
TEST_TARGET_URL=$URL npx jest scripts/vapt.test.ts --silent

echo "=== Layer 3: Playwright E2E ==="
TEST_TARGET_URL=$URL npx playwright test scripts/vapt.spec.ts --reporter=list

echo "✦ All layers complete."
```

---

## 5. CI/CD Integration

Copy `scripts/vapt-ci.yml` to `.github/workflows/vapt-ci.yml` in your repo.

Then add your site URL as a GitHub secret:

```
Repository Settings → Secrets and variables → Actions → New secret
Name:  WORDPRESS_URL
Value: https://your-wordpress-site.com
```

The pipeline runs:
- On every `push` and `pull_request`
- On a **nightly schedule** at 02:00 UTC

Artifacts (Playwright HTML reports) are uploaded on failure for 7 days.

---

## 6. Reading the Risk Score

The **Security Score** (0–100) is computed as:

```
Score = 100 - ( sum_of_unprotected_CVSS / 71.9 ) × 100
```

Where `71.9` is the sum of all 12 CVSS scores (the theoretical maximum exposure).

| Score Range | Status |
|---|---|
| 80 – 100 | 🟢 Secure |
| 50 – 79 | 🟡 Needs Attention |
| 0 – 49 | 🔴 Critical |

**Example:** If RISK-001 (7.5), RISK-007 (7.5), and RISK-012 (5.3) are unprotected:
```
Score = 100 - (20.3 / 71.9) × 100 = 100 - 28.2 = 71.8 → 🟡 Needs Attention
```

---

## 7. Prompt Reference

Quick-copy prompts for common tasks:

```bash
# Generate full dashboard
"Using VAPTSecure SixTee12, generate a production React dashboard for all 12 VAPT risks."

# Generate test suite for specific risks
"Using VAPTSecure SixTee12, generate Jest tests for RISK-003, RISK-005, and RISK-008."

# Generate remediation report
"Using VAPTSecure SixTee12, generate a remediation report for unprotected risks: RISK-002, RISK-007."

# Generate CI/CD pipeline only
"Using VAPTSecure SixTee12, generate a GitHub Actions workflow that runs all 3 test layers nightly."

# Generate compliance summary
"Using VAPTSecure SixTee12, generate a compliance mapping table for PCI-DSS, GDPR, and NIST CSF."

# Generate a risk card for a single risk
"Using VAPTSecure SixTee12, generate a detailed risk card component for RISK-002."

# Generate WordPress plugin snippet
"Using VAPTSecure SixTee12, generate the PHP remediation code for RISK-001 and RISK-012."
```

---

## 8. Troubleshooting

### Smoke test returns wrong status codes
- Ensure `TEST_TARGET_URL` has no trailing slash
- Check if the site uses a CDN or WAF that intercepts requests (may return 200 instead of 403)
- Try adding `-L` to follow redirects in the curl commands

### Jest tests time out
- Increase Jest timeout: `jest.setTimeout(15000)` at the top of `vapt.test.ts`
- Check if the target site has rate limiting on test runners (whitelist your CI IP)

### Playwright can't find elements (RISK-008 login test)
- Verify the WordPress login page is at `/wp-login.php` (some installs move it)
- Update the selector `#user_login` if a custom login plugin is active
- Check that the test user `invaliduser99999xxxx` doesn't accidentally exist

### Claude doesn't follow the skill aesthetic
- Make sure `SKILL.md` is fully included in context (not truncated)
- Re-state the aesthetic direction explicitly: *"Apply the Google Antigravity aesthetic from the skill"*
- For long conversations, re-paste the Design Aesthetic section

### Score always shows 0
- The score requires at least one test to have been run (`status !== "idle"`)
- After "Run Full Scan", score updates once all checks complete (~6 seconds)
