---
name: VAPTSecure Catalogue Full125
description: >
  A Google Antigravity–powered skill for loading, querying, triaging, and
  reporting against the VAPT-Risk-Catalogue-Full-125 v3.4.1 dataset.
  Triggers on any request involving vulnerability assessment, penetration-testing
  risk catalogues, CVSS scoring, remediation planning, or VAPT report generation
  that references the Full-125 data source.
version: 1.0.0
data_source: VAPT-Risk-Catalogue-Full-125-v3.4.1.json
catalogue_entries: 125
---

# VAPTSecure Catalogue Full125 Skill

## Purpose

This skill gives Claude structured, repeatable procedures for working with the
**VAPT-Risk-Catalogue-Full-125 v3.4.1** JSON dataset.  It covers:

- Loading and validating the catalogue from disk
- Querying entries by risk ID, severity, category, or keyword
- Generating triage summaries, remediation road-maps, and executive reports
- Exporting results to DOCX, XLSX, PDF, or Markdown
- Running automated scripts against a live WordPress/WooCommerce target

---

## Catalogue Schema (v3.4.1)

Each entry in `VAPT-Risk-Catalogue-Full-125-v3.4.1.json` follows this shape:

```json
{
  "risk_id":        "VAPT-001",
  "title":          "SQL Injection – Login Form",
  "category":       "Injection",
  "sub_category":   "SQL",
  "severity":       "Critical",
  "cvss_score":     9.8,
  "cvss_vector":    "CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H",
  "owasp_ref":      "A03:2021",
  "cwe_id":         "CWE-89",
  "description":    "...",
  "impact":         "...",
  "evidence":       "...",
  "remediation":    "...",
  "references":     ["https://owasp.org/..."],
  "tags":           ["wordpress","plugin","authentication"],
  "affected_asset": "wp-login.php",
  "discovered_at":  "2024-01-15T09:22:00Z",
  "status":         "Open"
}
```

> **Tip:** Use `scripts/validate_catalogue.py` to confirm every entry matches
> this schema before running any downstream tool.

---

## Severity Tiers

| Tier       | CVSS Range | SLA (remediate within) |
|------------|------------|------------------------|
| Critical   | 9.0–10.0   | 24 hours               |
| High       | 7.0–8.9    | 7 days                 |
| Medium     | 4.0–6.9    | 30 days                |
| Low        | 0.1–3.9    | 90 days                |
| Info       | 0.0        | Best effort            |

---

## Workflow

### 1 · Load & Validate

Before doing anything else, load and validate the data file:

```python
# scripts/load_catalogue.py
import json, sys
from pathlib import Path

CATALOGUE_PATH = Path(
    r"T:\~\Local925 Sites\hermasnet\app\public"
    r"\wp-content\plugins\VAPT-Secure\data"
    r"\VAPT-Risk-Catalogue-Full-125-v3.4.1.json"
)

def load(path=CATALOGUE_PATH):
    with open(path, encoding="utf-8") as f:
        data = json.load(f)
    entries = data if isinstance(data, list) else data.get("risks", data.get("entries", []))
    assert len(entries) == 125, f"Expected 125 entries, got {len(entries)}"
    return entries

if __name__ == "__main__":
    catalogue = load()
    print(f"✓ Loaded {len(catalogue)} entries")
```

Run `scripts/validate_catalogue.py` to enforce the full schema contract.

---

### 2 · Query

Use `scripts/query_catalogue.py` to filter entries:

```bash
# All Critical findings
python scripts/query_catalogue.py --severity Critical

# All Injection-category findings with CVSS ≥ 7.0
python scripts/query_catalogue.py --category Injection --min-cvss 7.0

# Find by risk ID
python scripts/query_catalogue.py --id VAPT-042

# Full-text keyword search
python scripts/query_catalogue.py --keyword "file upload"
```

---

### 3 · Triage

When triaging a finding, Claude must:

1. **Confirm severity** against CVSS score using the tier table above.
2. **Check `status`** — only `"Open"` and `"In Progress"` entries need action.
3. **Map remediation** — output a numbered action list from the `remediation` field.
4. **Cite references** — always include `owasp_ref`, `cwe_id`, and URLs from `references`.
5. **Attach evidence** — include the `evidence` string verbatim (do not paraphrase).

---

### 4 · Report Generation

Use the appropriate output skill depending on the deliverable:

| Deliverable         | Script / Skill                    |
|---------------------|-----------------------------------|
| Executive PDF       | `scripts/generate_report.py --format pdf` + `/mnt/skills/public/pdf/SKILL.md` |
| Detailed DOCX       | `scripts/generate_report.py --format docx` + `/mnt/skills/public/docx/SKILL.md` |
| Risk Register XLSX  | `scripts/generate_report.py --format xlsx` + `/mnt/skills/public/xlsx/SKILL.md` |
| Dev-friendly MD     | `scripts/generate_report.py --format md` |
| Slide Deck          | `scripts/generate_report.py --format pptx` + `/mnt/skills/public/pptx/SKILL.md` |

Always read the relevant output skill **before** generating the document.

---

### 5 · Status Update

After remediation is confirmed, update the entry status:

```bash
python scripts/update_status.py --id VAPT-042 --status Resolved --note "Patched in v2.1.4"
```

This writes back to the JSON file and appends an audit trail entry.

---

## Anti-patterns to Avoid

- **Never paraphrase `evidence`** — reproduce the evidence string exactly.
- **Never skip schema validation** before querying (stale data causes silent errors).
- **Never combine Critical + High into a single remediation wave** — SLAs differ.
- **Never output CVSS scores without the vector string** — scores alone are ambiguous.
- **Never mark a finding Resolved without a `--note`** — the audit trail is mandatory.

---

## Integration Points

| System          | How it connects                                      |
|-----------------|------------------------------------------------------|
| WordPress       | Plugin at `wp-content/plugins/VAPT-Secure/`         |
| Hermasnet App   | `app/public/` root — findings reference live routes |
| Google Drive    | Use `scripts/export_to_drive.py` for team sharing   |
| JIRA / Linear   | `scripts/create_tickets.py` maps risks → tickets    |
| Slack           | `scripts/notify_slack.py` for Critical SLA alerts   |

---

## File Map

```
VAPTSecure-Catalogue-Full125/
├── SKILL.md                        ← You are here
├── examples/
│   ├── example_query.md            ← Sample query interactions
│   ├── example_triage.md           ← End-to-end triage walkthrough
│   ├── example_report_prompt.md    ← Prompts that trigger report generation
│   └── sample_entry.json           ← A single well-formed catalogue entry
├── resources/
│   ├── CATALOGUE_SCHEMA.md         ← Full field-by-field schema reference
│   ├── SEVERITY_GUIDE.md           ← Severity tier decision tree
│   ├── OWASP_MAPPING.md            ← OWASP Top-10 → risk ID crosswalk
│   ├── CWE_MAPPING.md              ← CWE → risk ID crosswalk
│   └── REMEDIATION_TEMPLATES.md    ← Copy-paste remediation blocks
└── scripts/
    ├── load_catalogue.py           ← Load + basic assertion
    ├── validate_catalogue.py       ← Full schema validation
    ├── query_catalogue.py          ← Filter / search CLI
    ├── generate_report.py          ← Multi-format report generator
    ├── update_status.py            ← Write-back with audit trail
    ├── create_tickets.py           ← JIRA / Linear ticket creation
    ├── export_to_drive.py          ← Google Drive export
    └── notify_slack.py             ← Slack Critical-SLA notifier
```
