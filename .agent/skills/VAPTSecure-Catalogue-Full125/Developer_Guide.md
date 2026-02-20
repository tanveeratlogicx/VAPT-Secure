# Developer Guide — VAPTSecure Catalogue Full125

> For developers integrating the VAPT catalogue into dashboards, REST APIs,
> mobile apps, and CI/CD pipelines. Covers Interface Schema JSON generation
> in full detail.

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Catalogue Entry Model](#2-catalogue-entry-model)
3. [Interface Schema JSON — Concept](#3-interface-schema-json--concept)
4. [Schema Types Reference](#4-schema-types-reference)
5. [Generating Interface Schema Files with Claude](#5-generating-interface-schema-files-with-claude)
6. [Catalogue Schema JSON](#6-catalogue-schema-json)
7. [UI Display Schema JSON](#7-ui-display-schema-json)
8. [Filter Schema JSON](#8-filter-schema-json)
9. [Combining Schemas — Full Integration Payload](#9-combining-schemas--full-integration-payload)
10. [Validation Against a Schema](#10-validation-against-a-schema)
11. [Script Reference](#11-script-reference)
12. [CI/CD Integration](#12-cicd-integration)
13. [Extending the Catalogue Schema](#13-extending-the-catalogue-schema)
14. [Error Reference](#14-error-reference)

---

## 1 · Architecture Overview

```
┌──────────────────────────────────────────────────────────────┐
│                    VAPT-Secure Plugin                        │
│   WordPress · Hermasnet · wp-content/plugins/VAPT-Secure/   │
└──────────────────────┬───────────────────────────────────────┘
                       │ writes
                       ▼
        VAPT-Risk-Catalogue-Full-125-v3.4.1.json
                       │
          ┌────────────┼────────────────────┐
          │            │                    │
          ▼            ▼                    ▼
    Claude Skill   Scripts CLI       Interface Schema
    (query/triage) (automation)      JSON Files
          │            │                    │
          ▼            ▼                    ▼
      Reports      JIRA/Linear      Dashboard / API /
    PDF/DOCX/XLSX   Tickets          Mobile App / CI
```

The **Interface Schema JSON** layer is the bridge between the raw catalogue
data and any consuming system. It describes the shape, types, labels, and
constraints of the data so that frontends, validators, and API consumers
can understand it without reading source code.

---

## 2 · Catalogue Entry Model

Every entry in the Full-125 catalogue is a JSON object. Here is the
**complete TypeScript-style type definition** for reference when building
consuming applications:

```typescript
interface VAPTEntry {
  // ── Required fields ───────────────────────────────────────
  risk_id:        string;           // "VAPT-001" … "VAPT-125"
  title:          string;           // max 120 chars
  category:       VAPTCategory;
  severity:       Severity;
  cvss_score:     number;           // 0.0 – 10.0
  cvss_vector:    string;           // "CVSS:3.1/AV:..."
  description:    string;
  impact:         string;
  evidence:       string;           // reproduce verbatim
  remediation:    string;           // numbered list
  affected_asset: string;
  discovered_at:  string;           // ISO 8601 UTC
  status:         Status;

  // ── Optional fields ───────────────────────────────────────
  sub_category?:       string;
  owasp_ref?:          string;      // "A03:2021"
  cwe_id?:             string;      // "CWE-89"
  references?:         string[];
  tags?:               string[];

  // ── v3.4.x extended fields ────────────────────────────────
  remediation_effort?: RemediationEffort;
  business_impact?:    string;
  resolved_at?:        string;      // ISO 8601 UTC
  audit_trail?:        AuditEntry[];
}

type Severity         = "Critical" | "High" | "Medium" | "Low" | "Info";
type Status           = "Open" | "In Progress" | "Resolved" | "Accepted Risk" | "False Positive";
type RemediationEffort = "Low" | "Medium" | "High" | "Very High";

type VAPTCategory =
  | "Injection"            | "Broken Authentication"
  | "Sensitive Data Exposure" | "XML External Entities"
  | "Broken Access Control"  | "Security Misconfiguration"
  | "Cross-Site Scripting"   | "Insecure Deserialization"
  | "Known Vulnerable Components" | "Insufficient Logging"
  | "Server-Side Request Forgery" | "Cryptographic Failures"
  | "Software Integrity Failures" | "Business Logic"
  | "File Upload"            | "Authentication Failures";

interface AuditEntry {
  timestamp:   string;   // ISO 8601
  actor:       string;
  from_status: Status;
  to_status:   Status;
  note:        string;
}
```

---

## 3 · Interface Schema JSON — Concept

An **Interface Schema JSON** is a machine-readable document that describes
*how to interpret, display, and interact with* the catalogue data. It is
**not** the catalogue itself — it is metadata about the catalogue.

Think of it like a configuration layer:

```
catalogue data  +  interface schema  =  working dashboard / API / form
```

This skill generates three kinds of Interface Schema:

| Schema | Filename | Drives |
|---|---|---|
| Catalogue Schema | `catalogue_schema.json` | Validators, form builders, API contracts |
| UI Display Schema | `ui_display_schema.json` | Tables, cards, column config, labels |
| Filter Schema | `filter_schema.json` | Search bars, dropdowns, facets |

All three can be combined into a single **Integration Payload** for consuming
systems that need everything in one request.

---

## 4 · Schema Types Reference

### 4.1 Catalogue Schema

Describes each **field** — its type, whether it's required, its allowed
values, and validation constraints.

Used by: JSON Schema validators, OpenAPI spec generators, form auto-builders,
import/export tools.

### 4.2 UI Display Schema

Describes how to **show** the data — which columns appear in a table, their
order, their labels, default sort, and whether they're filterable or sortable.

Used by: React/Vue table components, mobile list views, export column selectors.

### 4.3 Filter Schema

Describes the available **filter dimensions** — which fields can be filtered,
what type of filter control to render (dropdown, range slider, text input),
and what the valid values are.

Used by: Search UIs, faceted navigation, API query builders.

---

## 5 · Generating Interface Schema Files with Claude

### Via Prompt

Ask Claude to generate any schema type directly:

> "Generate a Catalogue Schema JSON for the VAPT Full-125 dataset."

> "Create a UI Display Schema JSON for a risk register dashboard table."

> "Build a Filter Schema JSON covering severity, status, category, CVSS range,
> and OWASP reference."

> "Generate all three Interface Schema files and combine them into a single
> integration payload."

Claude will produce well-formed JSON you can copy directly into your project.

### Via Script

You can also generate schemas programmatically using `generate_report.py`
extended with the `--format schema` flag (see §11 for the full CLI):

```bash
python scripts/generate_report.py --format schema --schema-type catalogue
python scripts/generate_report.py --format schema --schema-type ui
python scripts/generate_report.py --format schema --schema-type filter
python scripts/generate_report.py --format schema --schema-type all
```

Each writes a `.json` file to the current directory and prints the path.

---

## 6 · Catalogue Schema JSON

This is the **canonical schema** Claude produces for the v3.4.1 data model.
Use it as a JSON Schema (Draft 7 compatible) to validate entries.

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "$id": "https://hermasnet.local/vapt/schemas/catalogue-v3.4.1.json",
  "title": "VAPT Risk Catalogue Entry",
  "description": "A single vulnerability finding from VAPT-Risk-Catalogue-Full-125-v3.4.1",
  "type": "object",

  "required": [
    "risk_id", "title", "category", "severity",
    "cvss_score", "cvss_vector", "description",
    "impact", "evidence", "remediation",
    "affected_asset", "discovered_at", "status"
  ],

  "properties": {

    "risk_id": {
      "type": "string",
      "pattern": "^VAPT-\\d{3}$",
      "description": "Unique risk identifier. Format: VAPT-NNN (001–125).",
      "examples": ["VAPT-001", "VAPT-042", "VAPT-125"]
    },

    "title": {
      "type": "string",
      "minLength": 5,
      "maxLength": 120,
      "description": "Short human-readable name. Convention: {Type} – {Location}.",
      "examples": ["SQL Injection – Login Form", "RCE via File Upload"]
    },

    "category": {
      "type": "string",
      "enum": [
        "Injection", "Broken Authentication", "Sensitive Data Exposure",
        "XML External Entities", "Broken Access Control",
        "Security Misconfiguration", "Cross-Site Scripting",
        "Insecure Deserialization", "Known Vulnerable Components",
        "Insufficient Logging", "Server-Side Request Forgery",
        "Cryptographic Failures", "Software Integrity Failures",
        "Business Logic", "File Upload", "Authentication Failures"
      ]
    },

    "sub_category": {
      "type": "string",
      "description": "Optional finer classification, e.g. 'SQL', 'Stored XSS', 'JWT'."
    },

    "severity": {
      "type": "string",
      "enum": ["Critical", "High", "Medium", "Low", "Info"],
      "description": "Must align with cvss_score per tier table."
    },

    "cvss_score": {
      "type": "number",
      "minimum": 0.0,
      "maximum": 10.0,
      "multipleOf": 0.1,
      "description": "CVSS v3.1 base score."
    },

    "cvss_vector": {
      "type": "string",
      "pattern": "^CVSS:3\\.[01]/",
      "description": "Full CVSS v3.1 vector string.",
      "examples": ["CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H"]
    },

    "owasp_ref": {
      "type": "string",
      "pattern": "^A\\d{2}:202[0-9]$",
      "description": "OWASP Top-10 identifier.",
      "examples": ["A03:2021", "A01:2021"]
    },

    "cwe_id": {
      "type": "string",
      "pattern": "^CWE-\\d+$",
      "description": "MITRE CWE identifier.",
      "examples": ["CWE-89", "CWE-79"]
    },

    "description": {
      "type": "string",
      "minLength": 50,
      "description": "Full technical description of the vulnerability."
    },

    "impact": {
      "type": "string",
      "minLength": 20,
      "description": "Business and technical impact if exploited."
    },

    "evidence": {
      "type": "string",
      "description": "Verbatim proof-of-concept. Reproduce exactly in all reports."
    },

    "remediation": {
      "type": "string",
      "description": "Numbered action list. Each action on its own line prefixed 'N. '."
    },

    "references": {
      "type": "array",
      "items": { "type": "string", "format": "uri" },
      "description": "URLs to authoritative resources."
    },

    "tags": {
      "type": "array",
      "items": { "type": "string" },
      "description": "Lowercase free-form tags for search and filtering."
    },

    "affected_asset": {
      "type": "string",
      "description": "URL path, file path, or component name of the affected asset.",
      "examples": ["/wp-login.php", "wp-content/plugins/contact-form-7/"]
    },

    "discovered_at": {
      "type": "string",
      "format": "date-time",
      "pattern": "^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}Z$",
      "description": "ISO 8601 UTC timestamp of initial discovery."
    },

    "status": {
      "type": "string",
      "enum": ["Open", "In Progress", "Resolved", "Accepted Risk", "False Positive"]
    },

    "remediation_effort": {
      "type": "string",
      "enum": ["Low", "Medium", "High", "Very High"],
      "description": "v3.4.x — Estimated engineering effort to remediate."
    },

    "business_impact": {
      "type": "string",
      "description": "v3.4.x — Non-technical business-context impact statement."
    },

    "resolved_at": {
      "type": "string",
      "format": "date-time",
      "description": "v3.4.x — Populated by update_status.py when status → Resolved."
    },

    "audit_trail": {
      "type": "array",
      "description": "v3.4.x — Auto-populated by update_status.py.",
      "items": {
        "type": "object",
        "required": ["timestamp", "actor", "from_status", "to_status", "note"],
        "properties": {
          "timestamp":   { "type": "string", "format": "date-time" },
          "actor":       { "type": "string" },
          "from_status": { "type": "string" },
          "to_status":   { "type": "string" },
          "note":        { "type": "string", "minLength": 5 }
        }
      }
    }
  },

  "additionalProperties": false
}
```

---

## 7 · UI Display Schema JSON

Tells a frontend component how to render the catalogue in a table or card view.

```json
{
  "$schema": "https://hermasnet.local/vapt/schemas/meta/ui-display-schema.json",
  "version": "1.0.0",
  "resource": "vapt_catalogue",
  "display_name": "VAPT Risk Register",

  "table": {
    "default_sort": { "field": "cvss_score", "direction": "desc" },
    "page_size": 25,
    "row_highlight": {
      "field": "severity",
      "rules": [
        { "value": "Critical", "class": "row-critical", "hex": "#FF4444" },
        { "value": "High",     "class": "row-high",     "hex": "#FF8C00" },
        { "value": "Medium",   "class": "row-medium",   "hex": "#FFC107" },
        { "value": "Low",      "class": "row-low",      "hex": "#2196F3" },
        { "value": "Info",     "class": "row-info",     "hex": "#9E9E9E" }
      ]
    }
  },

  "columns": [
    {
      "field":      "risk_id",
      "label":      "Risk ID",
      "visible":    true,
      "sortable":   true,
      "filterable": false,
      "width":      "100px",
      "type":       "string",
      "link": {
        "target": "detail",
        "route":  "/vapt/{risk_id}"
      }
    },
    {
      "field":      "severity",
      "label":      "Severity",
      "visible":    true,
      "sortable":   true,
      "filterable": true,
      "width":      "110px",
      "type":       "badge",
      "badge_map": {
        "Critical": { "label": "Critical", "color": "#FF4444" },
        "High":     { "label": "High",     "color": "#FF8C00" },
        "Medium":   { "label": "Medium",   "color": "#FFC107" },
        "Low":      { "label": "Low",      "color": "#2196F3" },
        "Info":     { "label": "Info",     "color": "#9E9E9E" }
      }
    },
    {
      "field":      "cvss_score",
      "label":      "CVSS",
      "visible":    true,
      "sortable":   true,
      "filterable": true,
      "width":      "80px",
      "type":       "number",
      "format":     "0.0",
      "range":      { "min": 0.0, "max": 10.0 }
    },
    {
      "field":      "title",
      "label":      "Finding",
      "visible":    true,
      "sortable":   true,
      "filterable": true,
      "type":       "string",
      "truncate":   60
    },
    {
      "field":      "category",
      "label":      "Category",
      "visible":    true,
      "sortable":   true,
      "filterable": true,
      "width":      "180px",
      "type":       "string"
    },
    {
      "field":      "affected_asset",
      "label":      "Asset",
      "visible":    true,
      "sortable":   false,
      "filterable": true,
      "type":       "code",
      "truncate":   40
    },
    {
      "field":      "status",
      "label":      "Status",
      "visible":    true,
      "sortable":   true,
      "filterable": true,
      "width":      "130px",
      "type":       "badge",
      "badge_map": {
        "Open":           { "label": "Open",           "color": "#FF4444" },
        "In Progress":    { "label": "In Progress",    "color": "#FF8C00" },
        "Resolved":       { "label": "Resolved",       "color": "#4CAF50" },
        "Accepted Risk":  { "label": "Accepted Risk",  "color": "#9C27B0" },
        "False Positive": { "label": "False Positive", "color": "#9E9E9E" }
      }
    },
    {
      "field":      "owasp_ref",
      "label":      "OWASP",
      "visible":    true,
      "sortable":   true,
      "filterable": true,
      "width":      "110px",
      "type":       "string"
    },
    {
      "field":      "discovered_at",
      "label":      "Discovered",
      "visible":    true,
      "sortable":   true,
      "filterable": false,
      "width":      "130px",
      "type":       "datetime",
      "format":     "YYYY-MM-DD"
    },
    {
      "field":      "cvss_vector",
      "label":      "CVSS Vector",
      "visible":    false,
      "sortable":   false,
      "filterable": false,
      "type":       "code"
    },
    {
      "field":      "cwe_id",
      "label":      "CWE",
      "visible":    false,
      "sortable":   true,
      "filterable": true,
      "type":       "string"
    }
  ],

  "detail_view": {
    "sections": [
      {
        "title": "Summary",
        "fields": ["risk_id","title","severity","cvss_score","cvss_vector",
                   "category","sub_category","owasp_ref","cwe_id",
                   "affected_asset","status","discovered_at"]
      },
      {
        "title": "Technical Detail",
        "fields": ["description","impact"]
      },
      {
        "title": "Evidence",
        "fields": ["evidence"],
        "render_as": "code_block"
      },
      {
        "title": "Remediation",
        "fields": ["remediation"],
        "render_as": "numbered_list"
      },
      {
        "title": "References",
        "fields": ["references"],
        "render_as": "link_list"
      },
      {
        "title": "Audit Trail",
        "fields": ["audit_trail"],
        "render_as": "timeline"
      }
    ]
  }
}
```

---

## 8 · Filter Schema JSON

Tells the UI what filter controls to render and with what options.

```json
{
  "$schema": "https://hermasnet.local/vapt/schemas/meta/filter-schema.json",
  "version": "1.0.0",
  "resource": "vapt_catalogue",

  "filters": [

    {
      "id":        "severity",
      "label":     "Severity",
      "field":     "severity",
      "type":      "multi_select",
      "operator":  "in",
      "options": [
        { "value": "Critical", "label": "🔴 Critical", "count_field": true },
        { "value": "High",     "label": "🟠 High",     "count_field": true },
        { "value": "Medium",   "label": "🟡 Medium",   "count_field": true },
        { "value": "Low",      "label": "🔵 Low",      "count_field": true },
        { "value": "Info",     "label": "⚪ Info",     "count_field": true }
      ],
      "default": ["Critical", "High"]
    },

    {
      "id":        "status",
      "label":     "Status",
      "field":     "status",
      "type":      "multi_select",
      "operator":  "in",
      "options": [
        { "value": "Open",           "label": "Open" },
        { "value": "In Progress",    "label": "In Progress" },
        { "value": "Resolved",       "label": "Resolved" },
        { "value": "Accepted Risk",  "label": "Accepted Risk" },
        { "value": "False Positive", "label": "False Positive" }
      ],
      "default": ["Open", "In Progress"]
    },

    {
      "id":       "cvss_range",
      "label":    "CVSS Score",
      "field":    "cvss_score",
      "type":     "range_slider",
      "operator": "between",
      "min":      0.0,
      "max":      10.0,
      "step":     0.1,
      "default":  { "min": 0.0, "max": 10.0 }
    },

    {
      "id":       "category",
      "label":    "Category",
      "field":    "category",
      "type":     "multi_select",
      "operator": "in",
      "options": [
        { "value": "Injection",                    "label": "Injection" },
        { "value": "Broken Access Control",        "label": "Broken Access Control" },
        { "value": "Security Misconfiguration",    "label": "Security Misconfiguration" },
        { "value": "Cross-Site Scripting",         "label": "XSS" },
        { "value": "Known Vulnerable Components",  "label": "Vulnerable Components" },
        { "value": "Authentication Failures",      "label": "Auth Failures" },
        { "value": "Cryptographic Failures",       "label": "Crypto Failures" },
        { "value": "Server-Side Request Forgery",  "label": "SSRF" },
        { "value": "File Upload",                  "label": "File Upload" },
        { "value": "Broken Authentication",        "label": "Broken Authentication" },
        { "value": "Sensitive Data Exposure",      "label": "Sensitive Data" },
        { "value": "Insecure Deserialization",     "label": "Insecure Deserialization" },
        { "value": "Insufficient Logging",         "label": "Insufficient Logging" },
        { "value": "Business Logic",               "label": "Business Logic" }
      ],
      "default": []
    },

    {
      "id":       "owasp_ref",
      "label":    "OWASP Top-10",
      "field":    "owasp_ref",
      "type":     "single_select",
      "operator": "equals",
      "options": [
        { "value": "A01:2021", "label": "A01 — Broken Access Control" },
        { "value": "A02:2021", "label": "A02 — Cryptographic Failures" },
        { "value": "A03:2021", "label": "A03 — Injection" },
        { "value": "A04:2021", "label": "A04 — Insecure Design" },
        { "value": "A05:2021", "label": "A05 — Security Misconfiguration" },
        { "value": "A06:2021", "label": "A06 — Vulnerable Components" },
        { "value": "A07:2021", "label": "A07 — Identification & Auth Failures" },
        { "value": "A08:2021", "label": "A08 — Software & Data Integrity" },
        { "value": "A09:2021", "label": "A09 — Security Logging Failures" },
        { "value": "A10:2021", "label": "A10 — SSRF" }
      ],
      "default": null
    },

    {
      "id":         "keyword",
      "label":      "Search",
      "field":      ["title", "description", "tags", "affected_asset"],
      "type":       "text_search",
      "operator":   "contains",
      "placeholder":"Search findings...",
      "default":    ""
    },

    {
      "id":       "discovered_after",
      "label":    "Discovered After",
      "field":    "discovered_at",
      "type":     "date_picker",
      "operator": "gte",
      "default":  null
    }
  ],

  "filter_logic": "AND",
  "url_param_prefix": "f_"
}
```

---

## 9 · Combining Schemas — Full Integration Payload

For consuming systems that want everything in one JSON document:

```json
{
  "meta": {
    "skill":          "VAPTSecure-Catalogue-Full125",
    "schema_version": "3.4.1",
    "generated_at":   "2025-02-20T00:00:00Z",
    "entry_count":    125
  },
  "catalogue_schema": { "...": "← full Catalogue Schema JSON from §6" },
  "ui_display_schema": { "...": "← full UI Display Schema JSON from §7" },
  "filter_schema":     { "...": "← full Filter Schema JSON from §8" }
}
```

**Generate with Claude:**
> "Generate the full integration payload combining all three Interface Schemas
> for the VAPT Full-125 catalogue."

**Generate via script:**
```bash
python scripts/generate_report.py --format schema --schema-type all \
  --output vapt_integration_payload.json
```

---

## 10 · Validation Against a Schema

Use the `catalogue_schema.json` from §6 with any JSON Schema Draft 7
validator to gate catalogue imports.

### Python (jsonschema)

```python
import json
from pathlib import Path
import jsonschema

schema  = json.loads(Path("catalogue_schema.json").read_text())
entries = json.loads(Path(
    r"T:\~\Local925 Sites\hermasnet\app\public"
    r"\wp-content\plugins\VAPT-Secure\data"
    r"\VAPT-Risk-Catalogue-Full-125-v3.4.1.json"
).read_text())

for entry in entries:
    try:
        jsonschema.validate(instance=entry, schema=schema)
    except jsonschema.ValidationError as e:
        print(f"✗ {entry.get('risk_id','?')}: {e.message}")
```

### Node.js (ajv)

```js
import Ajv from "ajv";
import addFormats from "ajv-formats";
import catalogue from "./VAPT-Risk-Catalogue-Full-125-v3.4.1.json" assert { type: "json" };
import schema from "./catalogue_schema.json" assert { type: "json" };

const ajv = new Ajv({ allErrors: true });
addFormats(ajv);
const validate = ajv.compile(schema);

catalogue.forEach(entry => {
  if (!validate(entry)) {
    console.error(entry.risk_id, validate.errors);
  }
});
```

---

## 11 · Script Reference

### `load_catalogue.py`

```
usage: load_catalogue.py [--path PATH]

Loads the catalogue JSON (both bare-array and root-object forms).
Asserts exactly 125 entries. Prints severity + status breakdown.

Arguments:
  --path PATH    Override default catalogue path
```

### `validate_catalogue.py`

```
usage: validate_catalogue.py [--path PATH] [--strict]

Validates every entry against the v3.4.1 schema contract.
Checks required fields, enum values, CVSS alignment, ISO 8601 dates.

Arguments:
  --path PATH    Override default catalogue path
  --strict       Enable strict category enum validation
  
Exit codes:
  0  All entries valid
  1  One or more violations found
```

### `query_catalogue.py`

```
usage: query_catalogue.py [options]

Options:
  --path PATH         Catalogue path override
  --id VAPT-NNN       Exact risk ID lookup
  --severity S[,S]    Filter by severity (comma-separated)
  --status ST[,ST]    Filter by status
  --category C        Category substring match
  --keyword KW        Full-text search (title + description + tags)
  --owasp A03:2021    Filter by OWASP ref (exact)
  --cwe CWE-89        Filter by CWE ID (exact)
  --min-cvss N        Minimum CVSS score (default: 0.0)
  --max-cvss N        Maximum CVSS score (default: 10.0)
  --json              Output raw JSON array
  --full              Show all fields (not just table summary)

Output: ANSI colour-coded table. Sorted CVSS desc.
```

### `generate_report.py`

```
usage: generate_report.py --format FORMAT [options]

Formats: pdf | docx | xlsx | md | pptx | schema

Options:
  --path PATH           Catalogue path override
  --output FILENAME     Output filename (auto-generated if omitted)
  --severity S[,S]      Filter by severity
  --status ST[,ST]      Filter by status
  --filter VAPT-N,...   Explicit risk ID list
  --delta PATH          Baseline JSON for delta report
  --title TEXT          Report title
  --client NAME         Client name (default: Hermasnet)
  --schema-type TYPE    catalogue | ui | filter | all  (with --format schema)
  --dry-run             Preview filtered entries without writing output

Outputs:
  <name>.md             Markdown source (always)
  <name>.data.json      JSON payload (for XLSX / schema formats)
  + format-specific instruction for Claude to render final document
```

### `update_status.py`

```
usage: update_status.py --id VAPT-NNN --status STATUS --note "text" [--actor EMAIL]

Required:
  --id VAPT-NNN        Risk ID to update
  --status STATUS      New status (Open | In Progress | Resolved |
                       Accepted Risk | False Positive)
  --note TEXT          Mandatory audit note

Optional:
  --path PATH          Catalogue path override
  --actor EMAIL/NAME   Who is making the change (default: system)

Side effects:
  Writes status change back to JSON.
  Appends audit_trail entry with timestamp.
  Sets resolved_at if status → Resolved.
```

### `create_tickets.py`

```
usage: create_tickets.py --ids "VAPT-N,..." --platform jira|linear [options]

JIRA options:
  --project KEY        JIRA project key
  --assignee USER      JIRA username
  --jira-url URL       JIRA base URL
  --jira-token TOKEN   JIRA API token
  --jira-email EMAIL   JIRA account email

Linear options:
  --team-id ID         Linear team ID
  --linear-token KEY   Linear API key
  --assignee ID        Linear member ID

Shared:
  --dry-run            Print payloads without calling any API
```

### `export_to_drive.py`

```
usage: export_to_drive.py --file FILE [FILE...] [options]

Required:
  --file FILE [FILE...]   File(s) to upload

Options:
  --folder-id ID          Google Drive folder ID
  --credentials PATH      Service account JSON key
                          (default: credentials/service_account.json)
  --dry-run               Preview without uploading
```

### `notify_slack.py`

```
usage: notify_slack.py --webhook URL [options]

Options:
  --webhook URL         Slack Incoming Webhook URL
                        (or env var VAPT_SLACK_WEBHOOK)
  --channel CHANNEL     Target channel (default: #security-alerts)
  --severity S[,S]      Filter by severity to notify about
  --ids "VAPT-N,..."    Specific risk IDs to notify about
  --summary             Send full catalogue digest instead of individual findings
  --dry-run             Print payload without sending
  --path PATH           Catalogue path override
```

---

## 12 · CI/CD Integration

Add VAPT catalogue validation as a gate in your pipeline:

### GitHub Actions

```yaml
name: VAPT Catalogue Gate

on:
  push:
    paths:
      - 'wp-content/plugins/VAPT-Secure/data/*.json'

jobs:
  validate:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Set up Python
        uses: actions/setup-python@v5
        with:
          python-version: '3.11'

      - name: Validate VAPT Catalogue
        run: |
          cd VAPTSecure-Catalogue-Full125
          python scripts/validate_catalogue.py \
            --path "${{ github.workspace }}/wp-content/plugins/VAPT-Secure/data/VAPT-Risk-Catalogue-Full-125-v3.4.1.json" \
            --strict

      - name: Check for new Critical findings
        run: |
          python scripts/query_catalogue.py \
            --path "${{ github.workspace }}/wp-content/plugins/VAPT-Secure/data/VAPT-Risk-Catalogue-Full-125-v3.4.1.json" \
            --severity Critical --status Open --json \
            | python -c "
          import json,sys
          findings = json.load(sys.stdin)
          if findings:
              print(f'::error::🔴 {len(findings)} Critical finding(s) still Open!')
              sys.exit(1)
          "
```

### Pre-commit Hook

```bash
#!/bin/sh
# .git/hooks/pre-commit
python VAPTSecure-Catalogue-Full125/scripts/validate_catalogue.py \
  --path "wp-content/plugins/VAPT-Secure/data/VAPT-Risk-Catalogue-Full-125-v3.4.1.json"
```

---

## 13 · Extending the Catalogue Schema

To add custom fields beyond the v3.4.1 spec:

1. Add the field definition to `catalogue_schema.json` under `"properties"`.
2. Remove `"additionalProperties": false` OR add your field to the allowlist.
3. Update `validate_catalogue.py` to include the new field in `REQUIRED_FIELDS`
   if it's mandatory.
4. Regenerate `ui_display_schema.json` and `filter_schema.json` via Claude:

> "The VAPT catalogue now has a new field called `ticket_id` (string, optional)
> that stores the linked JIRA ticket key. Update the UI Display Schema and
> Filter Schema to include it."

5. Bump the schema version in `$id` and `meta.schema_version`.

---

## 14 · Error Reference

| Error | Cause | Fix |
|---|---|---|
| `FileNotFoundError: Catalogue not found` | Wrong path | Pass `--path` with the correct absolute path |
| `AssertionError: Expected 125 entries, found N` | Truncated or filtered JSON | Check plugin output; re-run scan |
| `CVSS score X out of range for severity Y` | Data entry mismatch | Correct `severity` or `cvss_score` in the JSON |
| `Missing required field: 'evidence'` | Incomplete entry | Populate `evidence` in the VAPT-Secure plugin output |
| `Invalid risk_id format` | Malformed ID | Must match `VAPT-NNN` (3 digits, zero-padded) |
| `Slack API error: HTTP 400` | Malformed Block Kit payload | Check channel name starts with `#`; verify webhook URL |
| `JIRA 401 Unauthorized` | Invalid API token | Regenerate token at `id.atlassian.com` |
| `Google Drive 403 Forbidden` | Service account not shared on folder | Share Drive folder with service account email |
