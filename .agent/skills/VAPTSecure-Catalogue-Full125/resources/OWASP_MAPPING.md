# OWASP Top-10 (2021) → VAPT Risk ID Crosswalk

Use this mapping to quickly find all catalogue entries related to a given
OWASP category.  Risk IDs are placeholders — exact IDs depend on your scan;
update this file after each engagement.

---

## A01:2021 – Broken Access Control

Typical risk IDs: VAPT-010, VAPT-011, VAPT-012, VAPT-013, VAPT-014

Common findings in WordPress/hermasnet context:
- Unauthenticated access to wp-admin endpoints
- IDOR (Insecure Direct Object Reference) on user profile pages
- Missing capability checks in REST API endpoints
- Privilege escalation via role manipulation
- Directory listing on `/wp-content/uploads/`

---

## A02:2021 – Cryptographic Failures

Typical risk IDs: VAPT-020, VAPT-021, VAPT-022

Common findings:
- Plaintext passwords in `wp-config.php` backup files
- Weak MD5/SHA-1 password hashing in legacy plugins
- SSL/TLS misconfiguration (TLS 1.0/1.1 enabled)
- Sensitive data transmitted over HTTP

---

## A03:2021 – Injection

Typical risk IDs: VAPT-001, VAPT-002, VAPT-003, VAPT-004, VAPT-005

Common findings:
- SQL Injection in login form, search, contact forms
- Command injection via plugin file manager
- LDAP injection in directory integrations
- XML injection in XMLRPC endpoint

---

## A04:2021 – Insecure Design

Typical risk IDs: VAPT-030, VAPT-031

Common findings:
- No rate-limiting on login attempts (brute-force possible)
- Password reset tokens predictable or long-lived
- Business logic flaws in e-commerce checkout

---

## A05:2021 – Security Misconfiguration

Typical risk IDs: VAPT-040, VAPT-041, VAPT-042, VAPT-043, VAPT-044, VAPT-045

Common findings:
- WordPress debug mode enabled in production (`WP_DEBUG=true`)
- Exposed `.env` file with DB credentials
- Default admin username `admin` not changed
- XML-RPC enabled and not firewalled
- Publicly readable `wp-config.php` backup (`wp-config.php.bak`)

---

## A06:2021 – Vulnerable and Outdated Components

Typical risk IDs: VAPT-050, VAPT-051, VAPT-052 … VAPT-070

Common findings (the largest category for WordPress sites):
- Outdated WordPress core
- Outdated plugins with known CVEs
- Outdated themes with XSS vulnerabilities
- PHP version end-of-life

---

## A07:2021 – Identification and Authentication Failures

Typical risk IDs: VAPT-075, VAPT-076, VAPT-077, VAPT-078

Common findings:
- No MFA on wp-admin
- Session tokens not invalidated on logout
- Weak password policy (no minimum length enforcement)
- User enumeration via author archive (`/?author=1`)

---

## A08:2021 – Software and Data Integrity Failures

Typical risk IDs: VAPT-080, VAPT-081

Common findings:
- Auto-update disabled for plugins (unverified updates)
- Plugin files modified (supply chain indicator)
- Unverified plugin sources (not from wordpress.org)

---

## A09:2021 – Security Logging and Monitoring Failures

Typical risk IDs: VAPT-085, VAPT-086

Common findings:
- No login failure logging
- No WAF / IDS alerting configured
- Audit logs not shipped off-server

---

## A10:2021 – Server-Side Request Forgery (SSRF)

Typical risk IDs: VAPT-090, VAPT-091

Common findings:
- SSRF via plugin URL-fetch functionality
- WordPress pingback SSRF to internal services
- SSRF to cloud metadata endpoint (169.254.169.254)

---

## Additional Categories (outside OWASP Top-10)

| Category                     | Typical Risk IDs        |
|------------------------------|-------------------------|
| Cross-Site Scripting (XSS)   | VAPT-095 – VAPT-105     |
| File Upload Vulnerabilities  | VAPT-007, VAPT-008, VAPT-009 |
| Business Logic               | VAPT-110 – VAPT-115     |
| Sensitive Data Exposure      | VAPT-116 – VAPT-120     |
| Miscellaneous / Info         | VAPT-121 – VAPT-125     |
