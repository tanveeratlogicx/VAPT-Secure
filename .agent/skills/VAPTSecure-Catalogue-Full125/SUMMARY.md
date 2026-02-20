# VAPTSecure Catalogue Full125 — Summary

> One-page capability overview for stakeholders and onboarding.

---

## What It Is

**VAPTSecure Catalogue Full125** is a Google Antigravity Claude skill that
wraps the 125-entry `VAPT-Risk-Catalogue-Full-125-v3.4.1.json` dataset in a
set of structured procedures, scripts, and documentation. It enables Claude
to act as an intelligent security analyst assistant — querying findings,
producing triage decisions, generating client-ready reports, and exporting
machine-readable Interface Schemas for downstream system integration.

---

## The Data Source

| Property | Value |
|---|---|
| File | `VAPT-Risk-Catalogue-Full-125-v3.4.1.json` |
| Plugin | `VAPT-Secure` (WordPress) |
| Platform | Hermasnet (`hermasnet/app/public/`) |
| Total Entries | 125 vulnerability findings |
| Schema Version | v3.4.1 |
| Entry Fields | 17 standard + 4 extended (v3.4.x) |

---

## Severity Distribution (typical Full-125 engagement)

| Severity | Typical Count | SLA |
|---|---|---|
| 🔴 Critical | 3–8 | 24 hours |
| 🟠 High | 12–20 | 7 days |
| 🟡 Medium | 40–55 | 30 days |
| 🔵 Low | 25–35 | 90 days |
| ⚪ Info | 8–15 | Best effort |

---

## Core Capabilities at a Glance

```
┌─────────────────────────────────────────────────────────────┐
│                  VAPTSecure Catalogue Full125                │
├──────────────┬──────────────┬──────────────┬────────────────┤
│   LOAD &     │    QUERY &   │   REPORT &   │   INTEGRATE    │
│   VALIDATE   │   TRIAGE     │   EXPORT     │   & NOTIFY     │
├──────────────┼──────────────┼──────────────┼────────────────┤
│ Schema check │ By severity  │ PDF (exec)   │ JIRA tickets   │
│ 125 count    │ By status    │ DOCX (full)  │ Linear tickets │
│ CVSS align   │ By keyword   │ XLSX (reg.)  │ Slack alerts   │
│ Audit trail  │ By OWASP/CWE │ PPTX (deck)  │ Google Drive   │
│ Write-back   │ By risk ID   │ MD (dev)     │ Interface JSON │
└──────────────┴──────────────┴──────────────┴────────────────┘
```

---

## Interface Schema Generation

A key capability of this skill is producing **Interface Schema JSON files** —
structured JSON documents that describe the shape of VAPT data for consumption
by frontend dashboards, REST APIs, mobile apps, and CI/CD pipelines.

Three schema types are supported:

| Schema Type | Use Case |
|---|---|
| `catalogue_schema.json` | Full field definitions — drives form builders and validators |
| `ui_display_schema.json` | Column visibility, labels, sort order — drives dashboard tables |
| `filter_schema.json` | Available filter dimensions and their enum values — drives search UIs |

See `Developer_Guide.md` for the complete generation workflow.

---

## Skill Files

| File | Purpose |
|---|---|
| `SKILL.md` | Master instructions — Claude reads this first |
| `README.md` | Setup, structure, quick start |
| `SUMMARY.md` | This file |
| `User_Guide.md` | Non-technical walkthrough for analysts |
| `Developer_Guide.md` | Integration, schema generation, API reference |

---

## Scripts at a Glance

| Script | One-liner |
|---|---|
| `load_catalogue.py` | Load JSON + print severity & status stats |
| `validate_catalogue.py` | Enforce v3.4.1 schema contract on all 125 entries |
| `query_catalogue.py` | Filter/search CLI with colour-coded output |
| `generate_report.py` | Produce PDF/DOCX/XLSX/PPTX/MD from filtered entries |
| `update_status.py` | Write status + mandatory audit trail back to JSON |
| `create_tickets.py` | Create JIRA or Linear tickets from findings |
| `export_to_drive.py` | Upload reports to Google Drive via service account |
| `notify_slack.py` | Send Slack Block Kit alerts for Critical findings |

---

## Typical Workflow

```
Scan completes
     │
     ▼
validate_catalogue.py ──► 125/125 ✓
     │
     ▼
query_catalogue.py --severity Critical --status Open
     │
     ├──► update_status.py (mark In Progress)
     │
     ├──► create_tickets.py (JIRA / Linear)
     │
     ├──► notify_slack.py (Critical SLA alert)
     │
     ▼
generate_report.py --format docx
     │
     ▼
export_to_drive.py (share with client)
```

---

## Who Uses This Skill

| Role | Primary Actions |
|---|---|
| **Security Analyst** | Triage, status updates, ticket creation |
| **Dev Lead** | Query by category/CWE, access remediation templates |
| **Project Manager** | Run Markdown/XLSX summaries, track SLA status |
| **Client / Exec** | Receive PDF/PPTX executive report |
| **Frontend Dev** | Consume Interface Schema JSON for dashboard integration |
