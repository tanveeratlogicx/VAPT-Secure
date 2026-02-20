# VAPTSchema Builder Developer Guide

## Core Concept
The VAPTBuilder dynamically generates its UI and enforces security controls entirely via JSON schemas (`generated_schema` in the `vapt_feature_meta` table). When evaluating a risk from the `VAPT-Risk-Catalogue-Full-125-v3.4.1.json`, this skill translates the raw narrative and steps into this executable mapping.

## The Generation Pipeline

When a developer (or the AI) is prompted to generate an Interface Schema for a Risk ID (e.g. `RISK-001`):

1. **Locate Source Truth**: Search `VAPT-Risk-Catalogue-Full-125-v3.4.1.json` for the exact `risk_id`.
2. **Determine Driver**: Analyze `protection.automated_protection.enforcer` and `implementation_targets` (e.g., does it say `.htaccess`, `wp-config.php`, `PHP Functions`, `Cloudflare`, `IIS`/`web.config`, or `Caddy`?). Match this to the Enforcer Pattern Library.
3. **Map the Mappings**: 
    - The `controls[0].key` MUST strictly match the key in `enforcement.mappings`.
    - Extract the raw string from `implementation_steps[0].code` (or a known hook driver method).
    - Properly escape quotes (`"`) and newlines (`\n`) for valid JSON. Ensure XML/JSON payloads inside configuration mappings are perfectly preserved and escaped.
4. **Determine the Probe**: Parse `testing.test_payloads` to find the automated URL and method. Assign `universal_probe` and populate `test_config`.
5. **Output**: Return ONLY the JSON object.

## Avoiding Common Mistakes (Hallucinations)
- **Do not invent hooks**: If the driver is `hook`, stick to known driver methods (e.g., `block_xmlrpc`, `limit_login_attempts`).
- **Do not wrap `htaccess` rules unnecessarily**: `VAPT_Htaccess_Driver` handles the `# BEGIN VAPT` and `# END VAPT` wrappers internally. Supply only the raw directives (e.g., `<IfModule...>...</IfModule>`).
- **Integer Status Codes**: `expected_status` inside `test_config` MUST be an integer (`403`), never a string (`"403"`).
- **Match the Key**: If your toggle creates the `key` `"UI-RISK-105-001"`, your `enforcement.mappings` must have the exact key `"UI-RISK-105-001"`. Any mismatch causes silent failures during enforcement.
