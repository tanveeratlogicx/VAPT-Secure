# Example: End-to-End Triage Walkthrough

This document walks through a complete triage session for a Critical finding.

---

## Scenario

The security team has just completed a scan of `hermasnet`. The VAPT-Secure
plugin has written 125 findings to the catalogue. The client needs a triage
decision and remediation instructions for the top three findings within 2 hours.

---

## Step 1 — Load & Validate

```bash
python scripts/load_catalogue.py
# ✓ Loaded 125 entries

python scripts/validate_catalogue.py
# ✓ 125/125 entries passed schema validation
# ✗ 0 schema violations found
```

---

## Step 2 — Identify Top Findings

```bash
python scripts/query_catalogue.py --severity Critical --status Open
```

Returns 3 Critical, Open findings — VAPT-001, VAPT-007, VAPT-019.

---

## Step 3 — Triage VAPT-001

```bash
python scripts/query_catalogue.py --id VAPT-001
```

Claude's triage output:

---

**VAPT-001 · SQL Injection – Login Form**
**Severity:** Critical (CVSS 9.8 · CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H)
**SLA:** Remediate within **24 hours**
**OWASP:** A03:2021 – Injection | **CWE:** CWE-89

**Impact**
Full database compromise; authentication bypass granting wp-admin access;
potential remote code execution via SQL FILE privilege.

**Evidence** *(verbatim)*
```
POST /wp-login.php HTTP/1.1
log=' OR 1=1--&pwd=x&wp-submit=Log+In

Response: HTTP/1.1 302 Found — authenticated as admin (user_id=1)
```

**Remediation Actions**
1. Update WordPress core to ≥ 6.5.3.
2. Replace raw `$wpdb->query()` calls with `$wpdb->prepare()` throughout the plugin.
3. Enable a WAF rule blocking SQL metacharacters in authentication fields.
4. Rotate all WordPress admin credentials and database passwords immediately.
5. Review and sanitise all other form inputs using `sanitize_text_field()` / `esc_sql()`.

**References**
- https://owasp.org/www-project-top-ten/2021/A03_2021-Injection
- https://cwe.mitre.org/data/definitions/89.html
- https://developer.wordpress.org/reference/classes/wpdb/prepare/

---

## Step 4 — Mark In Progress

```bash
python scripts/update_status.py \
  --id VAPT-001 \
  --status "In Progress" \
  --note "Dev team assigned; WAF rule deployed as interim mitigation 2024-03-10T16:00Z"
```

---

## Step 5 — Generate Triage Report

```bash
python scripts/generate_report.py \
  --filter "VAPT-001,VAPT-007,VAPT-019" \
  --format docx \
  --output "Hermasnet_CriticalTriage_2024-03-10.docx"
```

The DOCX report includes cover page, executive summary, per-finding detail
pages, and a remediation timeline Gantt chart.

---

## Step 6 — Create Tickets

```bash
python scripts/create_tickets.py \
  --ids "VAPT-001,VAPT-007,VAPT-019" \
  --project HERMASNET \
  --assignee security-team
```

Creates three tickets with priority = Critical, due date = now + 24 hours.

---

## Step 7 — Notify Slack

```bash
python scripts/notify_slack.py \
  --channel "#security-alerts" \
  --severity Critical \
  --ids "VAPT-001,VAPT-007,VAPT-019"
```

Posts a formatted Slack message tagging the on-call engineer.
