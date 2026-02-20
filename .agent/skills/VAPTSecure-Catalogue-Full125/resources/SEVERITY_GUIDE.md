# Severity Guide & Decision Tree

## Tier Definitions

| Tier     | CVSS v3.1 | Colour  | Remediation SLA | Escalation Required |
|----------|-----------|---------|-----------------|---------------------|
| Critical | 9.0–10.0  | 🔴 Red    | 24 hours        | Yes – CISO + CTO    |
| High     | 7.0–8.9   | 🟠 Orange | 7 days          | Yes – Security Lead |
| Medium   | 4.0–6.9   | 🟡 Yellow | 30 days         | No – Dev Lead only  |
| Low      | 0.1–3.9   | 🔵 Blue   | 90 days         | No                  |
| Info     | 0.0       | ⚪ Grey   | Best effort     | No                  |

---

## CVSS Score Alignment Check

Before accepting a severity label, verify alignment:

```
cvss_score 9.8 → severity MUST be "Critical"   ✓
cvss_score 7.2 → severity MUST be "High"        ✓
cvss_score 7.2 → severity labelled "Critical"   ✗ SCHEMA VIOLATION
```

`scripts/validate_catalogue.py` flags mismatches automatically.

---

## Severity Decision Tree

```
Is the finding exploitable remotely without authentication?
├── YES
│   ├── Does exploitation lead to full system/data compromise?
│   │   ├── YES → CRITICAL
│   │   └── NO  → HIGH
└── NO
    ├── Does exploitation require authentication?
    │   ├── Low privilege (any logged-in user)
    │   │   ├── Full data compromise possible? YES → HIGH
    │   │   └── Partial impact only?           YES → MEDIUM
    │   └── High privilege (admin only)
    │       ├── Significant impact?            YES → MEDIUM
    │       └── Minor impact / info leak?      YES → LOW
    └── Requires physical access or very complex conditions
        → LOW or INFO
```

---

## Contextual Severity Adjustment

The CVSS base score is the starting point. Adjust the *reported* severity
upward if any of the following environmental factors apply to hermasnet:

| Factor                                    | Adjustment      |
|-------------------------------------------|-----------------|
| PII / payment data in scope               | +1 tier         |
| Public-facing WordPress site              | +1 tier if auth bypass |
| No WAF in front of target                 | +0.5 (document) |
| Recent exploitation in the wild (CISA KEV)| +1 tier         |
| Dev/staging environment (not production)  | −1 tier         |

---

## SLA Clock Rules

- SLA starts at `discovered_at` timestamp in the catalogue entry.
- SLA is paused if status = `Accepted Risk` (requires written sign-off).
- SLA resets to 0 if the same finding re-opens after a False Positive classification.
- Breached SLAs must be logged in `audit_trail` with a mandatory escalation note.
