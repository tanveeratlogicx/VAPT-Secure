# VAPTSecure SixTee12 — OWASP 2025 Cross-Reference

## OWASP Top 10 2025 → Risk Mapping

### A01:2025 — Broken Access Control
Risks: **RISK-003**, **RISK-005**

| Risk | Title | CVSS | CWE |
|---|---|---|---|
| RISK-003 | Username Enumeration via REST API | 5.3 | CWE-204 |
| RISK-005 | Admin Username via Author Query | 5.3 | CWE-204 |

**Pattern:** Both risks expose user identity data to unauthenticated requests —
a direct access control failure on REST and URL-query endpoints.

---

### A02:2025 — Security Misconfiguration
Risks: **RISK-002**, **RISK-006**, **RISK-010**, **RISK-011**, **RISK-012**

| Risk | Title | CVSS | CWE |
|---|---|---|---|
| RISK-002 | XML-RPC Pingback DDoS | 7.5 | CWE-918 |
| RISK-006 | REST Endpoint Disclosure | 3.7 | CWE-200 |
| RISK-010 | Server Banner Grabbing | 3.7 | CWE-200 |
| RISK-011 | WordPress Version via readme.html | 5.3 | CWE-200 |
| RISK-012 | HSTS Not Implemented | 5.3 | CWE-319 |

**Pattern:** WordPress ships with insecure defaults (XML-RPC enabled, readme.html
accessible, no HSTS). These are pure misconfiguration risks — all remediable
through configuration changes without code modifications.

---

### A06:2025 — Insecure Design
Risks: **RISK-001**, **RISK-009**

| Risk | Title | CVSS | CWE |
|---|---|---|---|
| RISK-001 | wp-cron.php DoS Attack | 7.5 | CWE-400 |
| RISK-009 | No Rate Limiting on Contact Forms | 5.3 | CWE-770 |

**Pattern:** Resource exhaustion via design-level absence of throttling. WordPress
cron by design triggers on HTTP requests; forms by design accept unlimited input.

---

### A07:2025 — Identification and Authentication Failures
Risks: **RISK-004**, **RISK-007**, **RISK-008**

| Risk | Title | CVSS | CWE |
|---|---|---|---|
| RISK-004 | Email Flooding via Password Reset | 5.3 | CWE-770 |
| RISK-007 | No Rate Limiting on Login | 7.5 | CWE-307 |
| RISK-008 | Username Enumeration via Login Errors | 5.3 | CWE-203 |

**Pattern:** Authentication mechanism failures — no lockout (RISK-007), user
oracle via error messages (RISK-008), and reset endpoint abuse (RISK-004).
Together these form a complete brute-force attack chain.

---

## CWE Cross-Reference

| CWE | Name | Risks |
|---|---|---|
| CWE-200 | Exposure of Sensitive Information | RISK-006, RISK-010, RISK-011 |
| CWE-203 | Observable Discrepancy | RISK-008 |
| CWE-204 | Observable Response Discrepancy | RISK-003, RISK-005 |
| CWE-307 | Improper Restriction of Excessive Authentication Attempts | RISK-007 |
| CWE-319 | Cleartext Transmission of Sensitive Information | RISK-012 |
| CWE-400 | Uncontrolled Resource Consumption | RISK-001 |
| CWE-770 | Allocation of Resources Without Limits | RISK-004, RISK-009 |
| CWE-918 | Server-Side Request Forgery (SSRF) | RISK-002 |

---

## Attack Chain Analysis

### Chain 1: Reconnaissance → Brute Force
```
RISK-003 (enumerate usernames via REST)
  ↓
RISK-005 (confirm admin username via author query)
  ↓
RISK-008 (confirm username validity via login error)
  ↓
RISK-007 (brute force with no rate limit)
```
**Combined CVSS exposure: 23.4 — Critical attack chain**

### Chain 2: DDoS Amplification
```
RISK-002 (XML-RPC as DDoS reflector)
  → amplified to third-party targets via pingback.ping
RISK-001 (wp-cron DoS on own server)
  → combined effect: attacker both attacks others AND exhausts target resources
```

### Chain 3: Version Fingerprinting → Exploit
```
RISK-010 (Server banner reveals Apache/Nginx version)
  ↓
RISK-011 (readme.html reveals WordPress version)
  ↓
Attacker cross-references both versions against CVE database
  → Targeted exploit with known PoC
```

---

## Remediation Priority Order (Risk-Adjusted)

Remediate in this order for maximum risk reduction per hour of effort:

| Order | Risk | Effort | CVSS Removed | Cumulative Score Gain |
|---|---|---|---|---|
| 1st | RISK-002 | 1-2h | 7.5 | +10.4 pts |
| 2nd | RISK-007 | 2-4h | 7.5 | +20.9 pts |
| 3rd | RISK-001 | 1-2h | 7.5 | +31.3 pts |
| 4th | RISK-012 | 0.5h | 5.3 | +38.7 pts |
| 5th | RISK-003 | 2-3h | 5.3 | +46.0 pts |
| 6th | RISK-005 | 1h   | 5.3 | +53.4 pts |
| 7th | RISK-008 | 1h   | 5.3 | +60.8 pts |
| 8th | RISK-011 | 0.5h | 5.3 | +68.1 pts |
| 9th | RISK-004 | 2h   | 5.3 | +75.5 pts |
| 10th | RISK-009 | 2h   | 5.3 | +82.9 pts |
| 11th | RISK-006 | 1h   | 3.7 | +88.0 pts |
| 12th | RISK-010 | 0.5h | 3.7 | +93.1 pts |

*Estimated score if all 12 protected: **100/100***
