# VAPTSecure Catalogue Full125

> **Google Antigravity Skill** · v1.0.0 · VAPT-Risk-Catalogue-Full-125-v3.4.1

A structured Claude skill for loading, querying, triaging, reporting and
schema-exporting against the **VAPT-Risk-Catalogue-Full-125** dataset powering
the VAPT-Secure WordPress plugin on the Hermasnet platform.

---

## What This Skill Does

| Capability | Description |
|---|---|
| **Load & Validate** | Parse the 125-entry JSON catalogue and enforce the v3.4.1 schema contract |
| **Query** | Filter by severity, status, OWASP ref, CWE, keyword, or Risk ID |
| **Triage** | Produce structured triage output with SLA, evidence, and numbered remediation |
| **Report** | Generate PDF, DOCX, XLSX, PPTX, or Markdown reports |
| **Status Update** | Write status changes back to JSON with a mandatory audit trail |
| **Interface Schema** | Export well-formed JSON Interface Schemas for UI integration |
| **Ticket Creation** | Map findings to JIRA or Linear tickets automatically |
| **Drive Export** | Upload reports to Google Drive via service account |
| **Slack Alerts** | Post Critical SLA notifications to Slack channels |

---

## Directory Structure

```
VAPTSecure-Catalogue-Full125/
├── SKILL.md                        ← Core skill instructions for Claude
├── README.md                       ← This file
├── SUMMARY.md                      ← One-page capability overview
├── User_Guide.md                   ← End-user walkthrough (non-technical)
├── Developer_Guide.md              ← Developer reference + Interface Schema generation
├── LICENSE.txt
│
├── examples/
│   ├── example_query.md            ← Sample query prompts & expected outputs
│   ├── example_triage.md           ← End-to-end triage walkthrough
│   ├── example_report_prompt.md    ← Report generation prompts per format
│   └── sample_entry.json           ← A single well-formed v3.4.1 catalogue entry
│
├── resources/
│   ├── CATALOGUE_SCHEMA.md         ← Full field-by-field schema reference
│   ├── SEVERITY_GUIDE.md           ← Severity tiers, decision tree, SLA rules
│   ├── OWASP_MAPPING.md            ← OWASP Top-10 2021 → Risk ID crosswalk
│   ├── CWE_MAPPING.md              ← CWE ID → Risk ID crosswalk
│   └── REMEDIATION_TEMPLATES.md    ← Copy-paste fix blocks per vulnerability class
│
├── scripts/
│   ├── load_catalogue.py           ← Load + basic assertions + severity stats
│   ├── validate_catalogue.py       ← Full schema enforcement (strict / normal)
│   ├── query_catalogue.py          ← CLI filter / search tool
│   ├── generate_report.py          ← Multi-format report generator
│   ├── update_status.py            ← Status write-back with audit trail
│   ├── create_tickets.py           ← JIRA + Linear ticket creation
│   ├── export_to_drive.py          ← Google Drive upload
│   └── notify_slack.py             ← Slack Block Kit notifier
│
└── evals/
    └── evals.json                  ← 6 automated evaluation prompts
```

---

## Quick Start

### 1 · Place the Skill

Copy this folder into your Claude skills directory so Claude can read `SKILL.md`
when the skill is triggered.

### 2 · Point at Your Data File

All scripts default to:

```
T:\~\Local925 Sites\hermasnet\app\public\wp-content\plugins\VAPT-Secure\data\VAPT-Risk-Catalogue-Full-125-v3.4.1.json
```

Override with `--path` on any script if your path differs.

### 3 · Validate the Catalogue

```bash
python scripts/validate_catalogue.py
# ✓ 125/125 entries passed schema validation
```

### 4 · Run Your First Query

```bash
python scripts/query_catalogue.py --severity Critical --status Open
```

### 5 · Generate a Report

```bash
python scripts/generate_report.py --format docx --severity Critical,High
```

---

## Triggering the Skill via Prompt

Claude auto-activates this skill when prompts reference:

- The VAPT catalogue, VAPT-Secure plugin, or hermasnet
- Risk IDs in the format `VAPT-NNN`
- Requests to triage, query, or report on security findings
- Interface Schema or JSON schema generation for VAPT data

---

## Dependencies

| Script | Required Packages |
|---|---|
| All scripts | Python 3.10+ (stdlib only for core functions) |
| `export_to_drive.py` | `google-auth google-api-python-client` |
| `create_tickets.py` | stdlib `urllib` (no extra install) |
| `notify_slack.py` | stdlib `urllib` (no extra install) |

Install optional deps:
```bash
pip install google-auth google-auth-oauthlib google-api-python-client
```

---

## Related Skills

This skill delegates document rendering to Anthropic's built-in output skills:

| Format | Skill Path |
|---|---|
| PDF | `/mnt/skills/public/pdf/SKILL.md` |
| DOCX | `/mnt/skills/public/docx/SKILL.md` |
| XLSX | `/mnt/skills/public/xlsx/SKILL.md` |
| PPTX | `/mnt/skills/public/pptx/SKILL.md` |

---

## Version History

| Version | Date | Notes |
|---|---|---|
| 1.0.0 | 2025-02 | Initial release — Full-125 v3.4.1 support |

---

## Maintainer

Hermasnet · VAPT-Secure Plugin Team
