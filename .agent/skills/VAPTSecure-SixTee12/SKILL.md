---
name: VAPTSecure SixTee12
description: >
  VAPTSecure SixTee12 Skill — Generates Situation-Aware, Production-Ready
  Interfaces and Verification Test Suites for the VAPT-SixTee 12-Risk Catalogue
  (WordPress VAPT Protection Suite v3.4, OWASP Top 10 2025).
  Use when creating security dashboards, risk cards, live scan UIs, or
  automated test runners for any subset of the 12 defined risks.
version: "1.0.0"
schema_version: "3.4.0"
owasp_standard: "OWASP Top 10 2025"
data_source: "resources/risk-catalogue.json"
license: Complete terms in LICENSE.txt
---

# VAPTSecure SixTee12 Skill

## How to Load This Skill

This skill requires **two files** to be provided in context:

```
1. SKILL.md              ← You are reading this (instructions only)
2. risk-catalogue.json   ← All risk data  (single source of truth)
```

**All risk IDs, titles, CVSS scores, OWASP mappings, CWE tags, payloads,
remediation steps, and check configurations live exclusively in
`risk-catalogue.json`.** Never duplicate or hard-code risk data inside
generated code — always read from the catalogue. When generating interfaces
or tests, consume the JSON directly so that a single update to the catalogue
propagates everywhere automatically.

---

## Purpose

This skill instructs Claude to produce two classes of output from the catalogue:

1. **Situation-Aware Interfaces** — React / HTML dashboards, risk cards, live
   scan widgets, and remediation panels driven by live protection state.
2. **Verification Test Suites** — Bash smoke tests, Jest unit tests, Playwright
   E2E tests, and GitHub Actions CI/CD pipelines covering every check in the
   catalogue.

---

## Design Aesthetic — Google Antigravity

Commit fully to **Google Material Design 3 elevated** meets **zero-gravity
cyber-ops**. Every pixel must feel like a security operations centre floating
above the atmosphere.

### Typography
- Headings: `Google Sans Display` (700)
- Body: `Google Sans Text` (400/500)
- Code, values, IDs: `Roboto Mono` (400/700)
- Import all three from Google Fonts. Never use Inter, Arial, or system fonts.

### Colour System
Read colours from `metadata.severity_colors` and `metadata.score_bands` in
the catalogue JSON. Canonical values for reference:

| Role | Hex |
|---|---|
| Background (void) | `#08101C` |
| Surface (panel) | `#111827` |
| Surface elevated | `#1A2233` |
| High severity | `#FF4444` |
| Medium severity | `#FFAA00` |
| Low severity | `#00C896` |
| Accent (Google Blue) | `#4285F4` |
| Primary text | `#E8EAED` |
| Muted text | `#9AA0A6` |

### Motion
- Card entrances: staggered `animation-delay` (50ms per card)
- Unprotected HIGH risks: `@keyframes pulse-border` on the card border
- Severity dots on unprotected risks: `@keyframes pulseGlow` (scale + opacity)
- Status transitions: `transition: all 0.3s ease`
- Scan running state: `@keyframes pulse` on status text (opacity 1 → 0.4)

### Spatial Composition
- Cards: `border-radius: 12px`, `border-left: 3px solid {severity_color}`
- HIGH + unprotected: `box-shadow: 0 4px 24px {high_glow}, -3px 0 20px {high_glow}`
- Layout: CSS Grid, 3 columns desktop / 1 column mobile, `gap: 14px`

### Never
- Purple gradients on white backgrounds
- Generic card templates
- Inter / Roboto / Arial as the primary font
- Flat, shadowless designs

---

## Interface Generation Rules

### Before generating any UI — read the catalogue JSON first.
Derive all titles, severities, CVSS scores, OWASP tags, CWE badges, payloads,
and remediation content from `risk-catalogue.json`. Do not hard-code any
risk data into the generated code.

### Top Bar
- Skill branding (`VAPTSecure SixTee12` + shield icon)
- Site URL input or display
- `Run Full Scan` button with live elapsed timer during scan
- Dynamic risk score badge

### Stats Row
- Security Score ring (SVG arc, colour from `metadata.score_bands`)
- Protected count / Vulnerable count / High-severity-open count / Total (12)

### Filter Bar
- Severity filters: ALL / HIGH / MEDIUM / LOW
- Category filters: derived from unique `category` values in the catalogue
- Live count of visible vs total risks

### Risk Cards — render per risk object
- `risk.id` badge (Roboto Mono, Google Blue)
- Severity pill with pulsing dot when `status === 'unprotected'`
- Title (2-line truncation)
- CVSS arc gauge (SVG half-circle, colour from `metadata.severity_colors`)
- `risk.owasp` tag, `risk.cwe` badge(s), `risk.category` tag
- Status badge: `PROTECTED` / `VULNERABLE` / `SCANNING` / `PENDING`
- Expandable drawer:
  - `risk.summary`
  - `risk.attack_scenario` in a red-tinted box
  - `risk.payloads` as a Roboto Mono code block
  - `risk.remediation_steps[0]` summary in a green-tinted box
  - **Run Test** button — enabled when any payload has `"automated": true`

### Situation Awareness Rules
- Compute security score from `metadata.score_formula`
- Apply colour thresholds from `metadata.score_bands`
- HIGH unprotected cards must have animated glowing left border
- Score ring must animate smoothly as protection state changes

### Accessibility
- All interactive elements: `aria-label`
- Severity: always include text label alongside colour
- Focus ring: `outline: 2px solid #4285F4; outline-offset: 2px`

---

## Test Suite Generation Rules

### Before generating any tests — read `risks[*].payloads` from the JSON.
The payload fields `type`, `method`, `url`, `expected_status`, `automated`,
`forbidden_strings`, `forbidden_headers`, `required_header`, and
`minimum_max_age` are the authoritative test specification. Derive every
test case from them; do not hard-code values.

### Layer 1 — Bash Smoke Tests
- One curl check per payload where `automated: true`
- Colour output: ✅ green pass, ❌ red fail, ⚠️ yellow skip
- Emit `log_skip` with `risk.payloads[*].manual_instructions` for `automated: false`
- Compute and print final security score using `metadata.score_formula`
- Exit `0` all pass / `1` any fail

Patterns:
```
http_request:      curl -s -o/dev/null -w "%{http_code}" -X {METHOD} "{BASE}{URL}"
                   compare to payload.expected_status

header_inspection  curl -sI "{BASE}/"
(forbidden):       grep -qi "^{HEADER}:" → fail if found

header_inspection  curl -sI "https://{BASE}/"
(required):        grep -qi "{REQUIRED_HEADER}" → pass if found

rate_limit_test:   fire payload.attempts requests, then check final response
                   for payload.expected_status_after_limit (429 / 403)

login_test:        POST credentials, grep response for payload.forbidden_strings
                   → fail if any found
```

### Layer 2 — Jest Unit Tests
- One `describe` per risk: `{risk.id}: {risk.title}`
- One `it` per automated payload
- `beforeAll` fires the HTTP request; assertions in `it` blocks
- For `login_test`: assert each `forbidden_strings` entry is absent from response body
- For `header_inspection` (forbidden): assert `response.headers.get(h)` is null
- For `header_inspection` (required): assert header defined; check `minimum_max_age` if present
- Base URL: `process.env.TEST_TARGET_URL`

### Layer 3 — Playwright E2E Tests
- One `test.describe` per risk
- `request` fixture for API-level checks (status codes, headers)
- `page` fixture only for browser-rendering checks (e.g., RISK-008 login errors)
- Assert blocked endpoints return status ≠ 200
- For RISK-008: navigate to `/wp-login.php`, submit invalid credentials,
  assert error text does not contain any entry from `payloads[0].forbidden_strings`

### CI/CD Pipeline (GitHub Actions)
- Three sequential jobs: `vapt-smoke` → `vapt-jest` → `vapt-playwright`
- Triggers: `push`, `pull_request` to main/master + nightly cron `0 2 * * *`
- Target URL from `secrets.WORDPRESS_URL`
- Upload Playwright HTML report as artifact on failure (14-day retention)
- Final `vapt-summary` job writes results table to `$GITHUB_STEP_SUMMARY`
- Optional Slack notification on failure via `secrets.SLACK_WEBHOOK`

---

## Security Score — Computation

Formula from `metadata.score_formula`:

```
security_score = round( 100 - ( sum_of_unprotected_cvss / metadata.total_max_cvss * 100 ) )
```

- `metadata.total_max_cvss` = **71.9**
- A risk is "unprotected" when scan status is not `"pass"`
- Apply `metadata.score_bands` colour thresholds to the computed score
- Animate SVG `stroke-dasharray` transition in UI (1.2s ease)

---

## Implementation Checklist

Before delivering any output, verify:

- [ ] All risk data sourced from `risk-catalogue.json` — nothing hard-coded
- [ ] All 12 risks represented (`RISK-001` through `RISK-012`)
- [ ] Severity colours from `metadata.severity_colors` in the JSON
- [ ] Score formula from `metadata.score_formula` in the JSON
- [ ] HIGH + unprotected cards have animated glowing border
- [ ] Smoke test covers all 12 `check_id` values
- [ ] Jest covers all payloads where `automated: true`
- [ ] Playwright covers all browser-verifiable checks
- [ ] CI/CD YAML has push + nightly schedule triggers
- [ ] Google Antigravity aesthetic applied throughout

---

## Prompt Examples

```
"Using VAPTSecure SixTee12 (with risk-catalogue.json loaded), generate a
 production React dashboard for all 12 risks."

"Using VAPTSecure SixTee12, generate the complete Jest test suite reading
 all test cases from risk-catalogue.json."

"Using VAPTSecure SixTee12, generate an HTML remediation report for a site
 where RISK-002, RISK-007, and RISK-012 are unprotected."

"Using VAPTSecure SixTee12, generate a standalone risk card component for
 RISK-007, sourcing all data from risk-catalogue.json."
```
