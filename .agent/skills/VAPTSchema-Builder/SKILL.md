---
name: VAPTSchema Builder
description: Specialized skill for transforming VAPT-Risk-Catalogue-Full-125-v3.4.1.json definitions into highly accurate Interface Schema JSONs for the VAPTBuilder plugin. Uses a strict Enforcer Pattern Library to ensure >90% output accuracy.
version: "1.0.0"
schema_version: "3.4.1"
---

# VAPTSchema Builder Expert Skill

This skill acts as the precise translation layer between the raw VAPT Risk Catalogs (primarily `VAPT-Risk-Catalogue-Full-125-v3.4.1.json`) and the strict **Interface Schema JSON** format required by the VAPTBuilder Workbench.

## 🎯 Primary Goal
To achieve **>90% accuracy** (zero hallucination) when instructing an AI Agent to convert catalog `protection` definitions into VAPTBuilder `enforcement` and `controls` schemas.

## 🧠 Trigger Condition
Use this skill whenever asked to **generate an Interface Schema**, **build VAPTBuilder UI configuration JSON**, or **translate a risk catalog item** into an enforcement schema.

---

## 🏛️ The Enforcer Pattern Library (Mapping Rosetta Stone)

To eliminate AI hallucination, you must STRICTLY map the `protection.automated_protection` source data to the target schema `enforcement` object using these deterministic patterns:

### Pattern 1: `.htaccess` Enforcer
*   **Condition**: The catalog's `protection.automated_protection.enforcer` contains `.htaccess` or the `implementation_targets` includes `.htaccess`.
*   **Driver Assignment**: `"driver": "htaccess"`
*   **Mapping Logic**:
    *   **Key**: The `component_id` from `ui_configuration.components[0]` (e.g., `UI-RISK-002-001`).
    *   **Value**: The EXACT string from `protection.automated_protection.implementation_steps[0].code`.
    *   *Rule*: The value MUST be appropriately JSON-escaped (e.g., escaping quotes and newlines: `\n`). Do NOT wrap it in `# BEGIN VAPT` markers; the driver handles that. Wrap in `<IfModule>` if it's Apache rules and not already wrapped.

### Pattern 2: `wp-config.php` Enforcer
*   **Condition**: The catalog's `protection.automated_protection.enforcer` contains `wp-config.php`.
*   **Driver Assignment**: `"driver": "wp-config"`
*   **Mapping Logic**:
    *   **Key**: The `component_id` (e.g., `UI-RISK-001-001`).
    *   **Value**: The EXACT PHP constant definition from `implementation_steps[0].code` (e.g., `define('DISABLE_WP_CRON', true);`). No extra PHP tags.

### Pattern 3: Hook / PHP Function Enforcer
*   **Condition**: The enforcer indicates `PHP Functions`, API, or unified plugin hooks.
*   **Driver Assignment**: `"driver": "hook"`
*   **Mapping Logic**:
    *   **Key**: The `component_id`.
    *   **Value**: You MUST select the matching predefined hook driver method name. Examples include: `block_xmlrpc`, `limit_login_attempts`, `hide_wp_version`, `block_user_enumeration`, `disable_file_editors`, `add_security_headers`. Do NOT hallucinate raw PHP logic here.

### Pattern 4: Cloudflare Enforcer
*   **Condition**: The catalog's `protection.automated_protection.enforcer` contains `Cloudflare` or specifies WAF/Edge rules.
*   **Driver Assignment**: `"driver": "cloudflare"`
*   **Mapping Logic**:
    *   **Key**: The `component_id`.
    *   **Value**: The exact Cloudflare API configuration JSON string (e.g., a Firewall Rule or Page Rule representation) from `implementation_steps[0].code`. Properly escaped if placed within a JSON string.

### Pattern 5: IIS (`web.config`) Enforcer
*   **Condition**: The catalog's `protection.automated_protection.enforcer` contains `IIS` or `web.config`.
*   **Driver Assignment**: `"driver": "iis"`
*   **Mapping Logic**:
    *   **Key**: The `component_id`.
    *   **Value**: The EXACT `<rule>` XML string from `implementation_steps[0].code`. Must be appropriately JSON-escaped (escaping quotes and newlines).

### Pattern 6: Caddy Enforcer
*   **Condition**: The catalog's `protection.automated_protection.enforcer` contains `Caddy` or `Caddyfile`.
*   **Driver Assignment**: `"driver": "caddy"`
*   **Mapping Logic**:
    *   **Key**: The `component_id`.
    *   **Value**: The EXACT directive string for the `Caddyfile` from `implementation_steps[0].code`. Properly escaped.

---

## 🎛️ UI Controls Translation Rules

The `ui_configuration` object in the catalog tells you what UI elements to build. You must translate it into the `controls` array.

1.  **Toggles**:
    *   Locate `ui_configuration.components[type="toggle"]`.
    *   Schema Output: `{"type": "toggle", "label": "{label}", "key": "{component_id}", "default": {default_value}}`.
    *   *Critical*: The `key` MUST be the exact `component_id` used in the enforcement mapping above.
2.  **Test Actions (Verification)**:
    *   Translate `testing.test_payloads` where `automated: true` into a `test_action`.
    *   Schema Output: `{"type": "test_action", "label": "Verify Protection", "key": "verify_{risk_id}", "test_logic": "universal_probe", "test_config": {...}}`.
    *   Extract `test_config` details from the payload (e.g., map `method` to `method`, `url` to `path`, `expected_response` to `expected_status` as an integer).
3.  **Operational Notes**:
    *   If `protection.manual_steps` exist, add a layout-spanning textarea reading the manual steps as context/guidance for the user.

---

## 📋 Exact Output Template

When generating output, provide ONLY the JSON in this strict structure. Do not invent new top-level keys.

```json
{
  "controls": [
    {
      "type": "toggle",
      "label": "Extracted from ui_configuration.components[0].label",
      "key": "Exact component_id (e.g., UI-RISK-001-001)",
      "default": true
    },
    {
      "type": "test_action",
      "label": "Verify Protection",
      "key": "verify_risk_xyz",
      "test_logic": "universal_probe",
      "test_config": {
        "method": "Extracted from testing.test_payloads[0].method",
        "path": "Extracted from url",
        "expected_status": 403
      }
    }
  ],
  "enforcement": {
    "driver": "Mapped strictly via Enforcer Pattern Library",
    "mappings": {
      "Exact component_id": "Strict string mapped via Enforcer Pattern Library"
    }
  }
}
```

## ✅ Accuracy Checklist

Before returning the JSON, explicitly verify:
- [ ] Is the `enforcement.driver` one of: `"htaccess"`, `"wp-config"`, `"hook"`, `"cloudflare"`, `"iis"`, or `"caddy"`?
- [ ] Does the `enforcement.mappings` key EXACTLY match the `controls.key` of the primary toggle?
- [ ] Is the mapping value raw, properly escaped directive/code (for htaccess/wp-config) or a predefined method string (for hook)? No hallucinations?
- [ ] Is the `expected_status` in `test_config` an integer (e.g., `403` not `"403"`)?
