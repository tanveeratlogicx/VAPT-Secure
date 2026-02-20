# User Guide — VAPTSecure Catalogue Full125

> For security analysts, project managers, and anyone using the skill
> day-to-day. No coding knowledge required for most tasks.

---

## Table of Contents

1. [Getting Started](#1-getting-started)
2. [Talking to Claude About Your Findings](#2-talking-to-claude-about-your-findings)
3. [Querying the Catalogue](#3-querying-the-catalogue)
4. [Triaging a Finding](#4-triaging-a-finding)
5. [Updating a Finding's Status](#5-updating-a-findings-status)
6. [Generating Reports](#6-generating-reports)
7. [Understanding Severity & SLA](#7-understanding-severity--sla)
8. [OWASP & CWE Quick Reference](#8-owasp--cwe-quick-reference)
9. [Exporting & Sharing](#9-exporting--sharing)
10. [Common Questions](#10-common-questions)

---

## 1 · Getting Started

The VAPT-Secure plugin on your Hermasnet WordPress site automatically writes
scan results to:

```
T:\~\Local925 Sites\hermasnet\app\public\wp-content\plugins\VAPT-Secure\data\
VAPT-Risk-Catalogue-Full-125-v3.4.1.json
```

This skill teaches Claude how to read, understand, and work with that file.
You don't need to open the JSON file yourself — just ask Claude.

**Before your first session, ask Claude:**
> "Load and validate the VAPT catalogue and tell me what's in it."

Claude will confirm all 125 entries are present and show you a severity
and status breakdown.

---

## 2 · Talking to Claude About Your Findings

You can speak naturally. Here are example prompts that work well:

| What you want | What to say |
|---|---|
| See all critical issues | *"Show me all Critical findings that are still Open."* |
| Look up one finding | *"Give me the full details for VAPT-042."* |
| Search by topic | *"Find everything related to file uploads."* |
| See high-priority open items | *"What's still Open with a CVSS score above 7?"* |
| Check OWASP coverage | *"Which findings map to OWASP A03?"* |
| Get a report | *"Generate a Word document for all Critical and High findings."* |
| Mark something fixed | *"Mark VAPT-001 as Resolved — the patch was deployed today."* |

---

## 3 · Querying the Catalogue

### By Severity

> "Show me all High severity findings."

> "List everything that's Critical or High and still Open."

Claude will return a table showing Risk ID, Title, CVSS Score, Affected Asset,
and Status. Results are always sorted highest CVSS first.

### By Keyword

> "Find any findings about SQL injection."

> "Search the catalogue for anything mentioning wp-admin."

Claude searches across the title, description, impact, and tags of every
entry.

### By OWASP or CWE

> "What findings map to OWASP A05:2021?"

> "Find all CWE-79 findings."

Claude cross-references `resources/OWASP_MAPPING.md` and `resources/CWE_MAPPING.md`
to return a grouped, sorted list.

### By Risk ID

> "Pull up VAPT-019."

Claude returns the full record — every field — including verbatim evidence
and a numbered remediation checklist.

---

## 4 · Triaging a Finding

When you ask Claude to triage a finding, it will:

1. **Look up the full record** by Risk ID
2. **Confirm the severity tier** (Critical / High / Medium / Low / Info)
3. **State the SLA** — how many days you have to fix it
4. **Present the impact** — what happens if it's exploited
5. **Reproduce the evidence verbatim** — the exact proof-of-concept captured during the scan
6. **List remediation steps** — numbered, actionable instructions
7. **Cite the references** — OWASP, CWE, vendor advisory links

**Example prompt:**
> "Triage VAPT-007 for me."

**What Claude produces:**

```
VAPT-007 · Remote Code Execution via File Upload
Severity: Critical (CVSS 9.6)
SLA: Remediate within 24 hours

Impact: ...
Evidence: [verbatim request/response]
Remediation:
  1. Restrict MIME type allowlist...
  2. Rename files to UUID on upload...
  ...
References: [links]
```

> ⚠️ **Important:** Claude will never paraphrase the evidence — it always
> reproduces it exactly as recorded during the scan, so your team has an
> accurate record.

---

## 5 · Updating a Finding's Status

Once a finding has been fixed, accepted, or assessed as a false positive,
update its status so the catalogue reflects reality.

**Available statuses:**

| Status | Meaning |
|---|---|
| `Open` | Discovered, not yet actioned |
| `In Progress` | Fix is being worked on |
| `Resolved` | Fix deployed and verified |
| `Accepted Risk` | Client has accepted the risk (sign-off required) |
| `False Positive` | Confirmed not a real vulnerability |

**Example prompts:**

> "Mark VAPT-001 as In Progress — the dev team is working on it."

> "Set VAPT-042 to Resolved. The patch was deployed in version 2.1.4."

> "Mark VAPT-088 as Accepted Risk — the client signed off on this one."

Claude will record the change with a timestamp and your note, building an
audit trail you can review at any time.

> 📝 **Note:** You must always give a reason when changing status.
> Claude will ask if you forget.

---

## 6 · Generating Reports

### Executive PDF

Best for: sending to the client, senior management, or the CISO.

> "Generate an executive PDF summary of all findings."

Includes: risk heat-map, severity breakdown, top-10 findings table,
remediation road-map.

### Full DOCX Report

Best for: the detailed technical record, shared with the dev team or stored
as the engagement deliverable.

> "Create a full Word document covering all Critical and High findings with
> description, evidence, and remediation for each."

### Risk Register Spreadsheet (XLSX)

Best for: project managers tracking remediation progress.

> "Export all 125 findings to an Excel risk register."

Includes: all fields as columns, colour-coded rows, frozen header, sorted
by CVSS descending.

### Slide Deck (PPTX)

Best for: client presentation meetings.

> "Build a PowerPoint deck for the client review. One slide per Critical
> finding, plus an overview and next steps slide."

### Markdown (MD)

Best for: developers who want to paste findings into GitHub issues, Confluence,
or Notion.

> "Give me a Markdown report of all Open Medium findings."

---

## 7 · Understanding Severity & SLA

Every finding has a severity based on its CVSS score:

| Severity | CVSS Range | You Must Fix Within |
|---|---|---|
| 🔴 **Critical** | 9.0 – 10.0 | **24 hours** |
| 🟠 **High** | 7.0 – 8.9 | **7 days** |
| 🟡 **Medium** | 4.0 – 6.9 | **30 days** |
| 🔵 **Low** | 0.1 – 3.9 | **90 days** |
| ⚪ **Info** | 0.0 | Best effort |

The **SLA clock starts** at the `discovered_at` timestamp in the catalogue,
not from when you open the report.

**Check your SLA exposure at any time:**

> "Which Open findings have SLAs that started more than 3 days ago?"

---

## 8 · OWASP & CWE Quick Reference

You don't need to memorise these — Claude knows them. But here's a handy
reference for the most common categories in this catalogue:

| OWASP Code | Name | Common Finding Type |
|---|---|---|
| A01:2021 | Broken Access Control | Missing capability checks, IDOR |
| A02:2021 | Cryptographic Failures | Plaintext passwords, weak TLS |
| A03:2021 | Injection | SQL injection, command injection |
| A05:2021 | Security Misconfiguration | Debug mode on, exposed .env |
| A06:2021 | Vulnerable Components | Outdated plugins, old PHP |
| A07:2021 | Auth Failures | No MFA, user enumeration |
| A10:2021 | SSRF | Plugin URL-fetch, pingback abuse |

---

## 9 · Exporting & Sharing

### Send to Google Drive

> "Upload the report to Google Drive."

Claude uses `scripts/export_to_drive.py`. You'll need the target folder ID
from your Drive URL (the long string after `/folders/`).

### Create JIRA / Linear Tickets

> "Create JIRA tickets for all Critical and High findings."

> "Open Linear issues for VAPT-001, VAPT-007, and VAPT-019."

Claude automatically sets the ticket priority, due date (based on SLA),
and attaches the full finding description and evidence.

### Notify Slack

> "Send a Slack alert to #security-alerts for all Critical findings."

Claude posts a formatted message using Slack Block Kit — colour-coded by
severity, with each finding's Risk ID, title, CVSS, and SLA.

---

## 10 · Common Questions

**Q: Can I search across multiple severities at once?**
Yes: *"Show me Critical and High findings that are Open."*

**Q: Can I filter by the affected WordPress file?**
Yes: *"Find findings that affect wp-login.php."* (Claude uses keyword search
across the `affected_asset` field.)

**Q: Will the report include fixed findings?**
Only if you ask for them. Default filters show `Open` and `In Progress`.
Say *"include Resolved findings"* to add them.

**Q: What if I need to re-open a finding after it was marked Resolved?**
Just say: *"Re-open VAPT-042 — the fix was incomplete."* Claude sets status
back to `Open` and adds a note to the audit trail.

**Q: Can I see the audit history for a finding?**
Yes: *"Show me the full audit trail for VAPT-001."* Claude reads the
`audit_trail` array from the entry.

**Q: The catalogue has 125 entries but a new scan found new issues. Can I add them?**
New entries should be added by the VAPT-Secure plugin on the next scan.
Contact your system administrator or developer to trigger a re-scan.

**Q: Can I generate a report for just a subset of findings?**
Yes: *"Generate a DOCX report for VAPT-001, VAPT-007, and VAPT-019 only."*
