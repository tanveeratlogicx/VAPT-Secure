# VAPT-Risk-Catalogue-Full-125 v3.4.1 · Schema Reference

## Top-Level Structure

The JSON file may wrap entries in a root object OR be a bare array:

```json
// Form A — root object
{ "meta": { ... }, "risks": [ ... ] }

// Form B — bare array (v3.4.x default)
[ { "risk_id": "VAPT-001", ... }, ... ]
```

Use `scripts/load_catalogue.py` — it handles both forms automatically.

---

## Field Reference

### `risk_id` · string · **REQUIRED**
Format: `VAPT-NNN` where NNN is zero-padded to 3 digits (001–125).
Unique identifier. Used as the primary key in all scripts and ticket systems.

### `title` · string · **REQUIRED**
Short human-readable name for the finding. Max 120 characters.
Convention: `{Vulnerability Type} – {Location/Component}`

### `category` · string · **REQUIRED**
Top-level OWASP-aligned category. Enum values seen in v3.4.1:
`Injection`, `Broken Authentication`, `Sensitive Data Exposure`,
`XML External Entities`, `Broken Access Control`, `Security Misconfiguration`,
`Cross-Site Scripting`, `Insecure Deserialization`, `Known Vulnerable Components`,
`Insufficient Logging`, `Server-Side Request Forgery`, `Cryptographic Failures`,
`Software Integrity Failures`, `Business Logic`

### `sub_category` · string · optional
Finer-grained classification, e.g. `SQL`, `Stored XSS`, `JWT`, `S3 Bucket`.

### `severity` · string · **REQUIRED**
Enum: `Critical` | `High` | `Medium` | `Low` | `Info`
Must align with `cvss_score` per the tier table in SKILL.md.

### `cvss_score` · number · **REQUIRED**
CVSS v3.1 base score. Range: 0.0–10.0. Two decimal places maximum.

### `cvss_vector` · string · **REQUIRED**
Full CVSS v3.1 vector string.
Example: `CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H`

### `owasp_ref` · string · optional
OWASP Top-10 2021 identifier. Format: `AXX:2021`
Example: `A03:2021`

### `cwe_id` · string · optional
MITRE CWE identifier. Format: `CWE-NNN`
Example: `CWE-89`

### `description` · string · **REQUIRED**
Full technical description of the vulnerability. Min 50 characters.

### `impact` · string · **REQUIRED**
Business/technical impact if exploited.

### `evidence` · string · **REQUIRED**
Proof-of-concept or reproduction steps. **Reproduce verbatim in all reports.**
May contain raw HTTP requests/responses, command output, or screenshots paths.

### `remediation` · string · **REQUIRED**
Numbered action list for fixing the vulnerability.
Each action on its own line prefixed `N. `.

### `references` · array of string · optional
URLs to authoritative resources (OWASP, NVD, CWE, vendor advisories).

### `tags` · array of string · optional
Free-form lowercase tags for search/filtering.

### `affected_asset` · string · **REQUIRED**
URL path, file path, or component name of the affected asset.

### `discovered_at` · string (ISO 8601) · **REQUIRED**
DateTime when the finding was first observed.
Format: `YYYY-MM-DDTHH:MM:SSZ`

### `status` · string · **REQUIRED**
Enum: `Open` | `In Progress` | `Resolved` | `Accepted Risk` | `False Positive`

---

## Optional Extended Fields (v3.4.x additions)

### `remediation_effort` · string · optional
Enum: `Low` | `Medium` | `High` | `Very High`

### `business_impact` · string · optional
Narrative business-context impact (non-technical language for executives).

### `resolved_at` · string (ISO 8601) · optional
Populated by `update_status.py` when status → Resolved.

### `audit_trail` · array · optional
Auto-populated by `update_status.py`. Each entry:
```json
{
  "timestamp": "2024-03-10T16:00:00Z",
  "actor": "analyst@hermasnet.com",
  "from_status": "Open",
  "to_status": "In Progress",
  "note": "WAF rule deployed as interim mitigation"
}
```
