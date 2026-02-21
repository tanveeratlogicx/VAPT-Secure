---
name: VAPTSchema Builder
description: Specialized skill for transforming VAPT-Risk-Catalogue-Full-125-v3.4.1.json definitions into highly accurate Interface Schema JSONs for the VAPTBuilder plugin. Uses a strict Enforcer Pattern Library to ensure >90% output accuracy.
version: "1.2.0"
schema_version: "1.2.0"
---

# VAPTSchema Builder Expert Skill (v1.2.0)

This skill acts as the precise translation layer between the raw VAPT Risk Catalogs and the strict **Interface Schema JSON** format. Version 1.2 focus is on **Rewrite Rule Placement Reliability** and **Platform Parity**.

## 🎯 Primary Goal
To achieve **>90% accuracy** (zero hallucination) when instructing an AI Agent to convert catalog definitions into VAPTBuilder `enforcement` and `controls` schemas, specifically preventing "Dead Zone" rewrite failures.

## 🧠 Trigger Condition
Use this skill whenever asked to **generate an Interface Schema**, **build VAPTBuilder UI configuration JSON**, or **translate a risk catalog item** into an enforcement schema.

---

## 🏛️ The Enforcer Pattern Library (Mapping Rosetta Stone)

To eliminate AI hallucination, you must STRICTLY map the source data to the target schema `enforcement` object using these deterministic patterns:

### Pattern 1: `.htaccess` Enforcer (v1.2 UPDATED)
*   **Condition**: The catalog's enforcer or `implementation_targets` includes `.htaccess`.
*   **Driver Assignment**: `"driver": "htaccess"`
*   **Mapping Logic**:
    *   **Key**: The `component_id` from `ui_configuration.components[0]`.
    *   **Value**: The EXACT code from `enforcer_pattern_library_v1.2.json`.
    *   **CRITICAL v1.2 RULES**:
        1.  **Placement**: Always use `insertion_point: "before_wordpress_rewrite"`.
        2.  **Dead Zone Warning**: Do NOT place after `# END WordPress`. WordPress's `[L]` flag makes rules after it unreachable.
        3.  **Mandatory Wrapper**: All rewrite blocks MUST be wrapped in `<IfModule mod_rewrite.c>` and include `RewriteEngine On` and `RewriteBase /`.
        4.  **Target File**: For `RISK-020`, the target file MUST be `wp-content/uploads/.htaccess`.

### Pattern 2: `wp-config.php` Enforcer
*   **Condition**: The catalog's enforcer contains `wp-config.php`.
*   **Driver Assignment**: `"driver": "wp-config"`
*   **Mapping Logic**:
    *   **Key**: The `component_id`.
    *   **Value**: The EXACT PHP constant definition. No extra PHP tags. Must be placed BEFORE `wp-settings.php` requirement.

### Pattern 3: Hook / PHP Function Enforcer
*   **Condition**: The enforcer indicates `PHP Functions`.
*   **Driver Assignment**: `"driver": "hook"`
*   **Value**: Use predefined hook driver method names (e.g., `block_xmlrpc`, `block_user_enumeration`).

### Pattern 4: Cloudflare/IIS/Caddy Enforcers
*   **Driver Assignment**: `"driver": "cloudflare"`, `"driver": "iis"`, or `"driver": "caddy"`.
*   **Logic**: Map to `web_config_snippet` (IIS), `caddyfile_snippet` (Caddy), or `waf_custom_rule` (Cloudflare).
*   **Note**: If `implementation_type` is `notes_only`, output the note.

---

## 🎛️ UI Controls Translation Rules

1.  **Toggles**: Schema Output: `{"type": "toggle", "label": "{label}", "key": "{component_id}", "default": {default_value}}`.
2.  **Test Actions (Verification)**: `{"type": "test_action", "label": "Verify Protection", "key": "verify_{risk_id}", "test_logic": "universal_probe", "test_config": {...}}`.

---

## 📋 Exact Output Template

```json
{
  "controls": [ ... ],
  "enforcement": {
    "driver": "Mapped strictly via Enforcer Pattern Library",
    "mappings": {
      "Exact component_id": "Strict string mapped via Enforcer Pattern Library"
    }
  }
}
```

---

## ✅ Accuracy Checklist (v1.2 - 17 Point Rubric)

Before returning the JSON, score it against this 17-point rubric. **Threshold: ≥16/19 points.**

| # | Check Item | Weight |
|---|---|---|
| 1 | All component IDs match interface_schema exactly | 2 |
| 2 | Enforcement code read from pattern library, not hallucinated | 2 |
| 3 | Severity badge colors match `global_ui_config.severity_badge_colors` | 1 |
| 4 | Handler names follow naming conventions (`handleRISK{NNN}{Type}Change`) | 1 |
| 5 | Platform listed in `available_platforms` for this risk | 1 |
| 6 | VAPT block markers present in all enforcement code output | 1 |
| 7 | Verification command present and matches platform CLI | 1 |
| 8 | No forbidden patterns violated | 1 |
| 9 | **[HTACCESS]** No forbidden directives (`TraceEnable`, `ServerSignature`, etc.) | 2 |
| 10 | **[HTACCESS]** `RewriteEngine On` present before every block | 1 |
| 11 | **[HTACCESS]** `mod_headers` requirement noted for `Header` directives | 1 |
| 12 | **[HTACCESS]** `AllowOverride` requirement noted for `Options` directives | 1 |
| 13 | **[HTACCESS]** `target_file` = `wp-content/uploads/.htaccess` for RISK-020 | 1 |
| 14 | **[IIS]** Snippet includes URL Rewrite Module 2.1 requirement | 1 |
| 15 | **[CADDY]** Caddy output uses v2 syntax only | 1 |
| 16 | **[V1.2]** Rewrite blocks use `insertion_point=before_wordpress_rewrite` | 2 |
| 17 | **[V1.2]** Rewrite blocks wrapped in `<IfModule mod_rewrite.c>` with `RewriteBase /` | 2 |

