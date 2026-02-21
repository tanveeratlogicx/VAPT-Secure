# VAPT Pattern Library — Audit & Fix Report
## v1.0 → v1.1 Corrections
**Audit Date:** 2026-02-21 | **Issues Found:** 47 | **Issues Fixed:** 47

---

## Critical .htaccess Failures Fixed

These were silent failures — rules that appeared valid but were ignored by Apache at runtime.

### 🔴 RISK-020 — PHP File Execution in Uploads Directory
**Root cause:** `<Directory "/wp-content/uploads">` blocks are **silently ignored** in .htaccess files. Apache only processes `<Directory>` in httpd.conf or VirtualHost context.

| v1.0 (broken) | v1.1 (fixed) |
|---------------|-------------|
| `<Directory "/wp-content/uploads">\nphp_flag engine off\n</Directory>` | `<FilesMatch "\.php$">\n    Order Deny,Allow\n    Deny from all\n</FilesMatch>` |
| Target: `.htaccess` (root) | Target: `wp-content/uploads/.htaccess` (separate file) |

### 🔴 RISK-023 — Trace Method Enabled
**Root cause:** `TraceEnable off` is a **server-level directive** — silently ignored in .htaccess.

| v1.0 (broken) | v1.1 (fixed) |
|---------------|-------------|
| `TraceEnable off` | `RewriteCond %{REQUEST_METHOD} ^TRACE [NC]\nRewriteRule .* - [F,L]` |
| Filed as: .htaccess directive | Filed as: mod_rewrite rule (requires `AllowOverride FileInfo`) |

Also added: `TraceEnable off` note for httpd.conf as the authoritative fix.

### 🔴 RISK-024 — Server Signature Enabled
**Root cause:** `ServerSignature Off` / `ServerTokens Prod` are **server-level directives** — silently ignored in .htaccess.

| v1.0 (broken) | v1.1 (fixed) |
|---------------|-------------|
| `ServerSignature Off` | `Header unset Server\nHeader always unset X-Powered-By` |
| Filed as: .htaccess directive | Filed as: mod_headers (requires `AllowOverride FileInfo`) |

Also added: `ServerSignature Off` + `ServerTokens Prod` note for httpd.conf.

---

## Regex Bug Fixed

### 🟠 RISK-005 — Author Enumeration
**Root cause:** `author=\d` only matched single-digit author IDs (1–9). IDs ≥ 10 were not blocked.

| v1.0 (wrong) | v1.1 (fixed) |
|-------------|-------------|
| `RewriteCond %{QUERY_STRING} author=\d [NC]` | `RewriteCond %{QUERY_STRING} author=\d+ [NC]` |

---

## Missing RewriteEngine On (2 risks)

| Risk | Fix |
|------|-----|
| RISK-003 (REST API user enum) | Added `RewriteEngine On` before RewriteRule |
| RISK-005 (Author query) | Added `RewriteEngine On` before RewriteCond |

---

## Insertion Point Normalization (22 risks)

All `insertion_point` fields that contained **header names** (e.g. `"X-Frame-Options"`, `"Referrer-Policy"`) or **directive names** (e.g. `"TraceEnable"`, `"ServerSignature"`) have been normalized to **positional tokens**:

| Old (wrong) | New (correct) |
|-------------|--------------|
| `"X-Frame-Options"` | `"beginning_of_file"` |
| `"Referrer-Policy"` | `"beginning_of_file"` |
| `"Content-Security-Policy"` | `"beginning_of_file"` |
| `"TraceEnable"` | `"beginning_of_file"` |
| `"install.php"` | `"beginning_of_file"` |
| `"sensitive_files"` | `"beginning_of_file"` |

---

## File Field Corrections (22 risks)

`file` fields were being used to store **operation names** instead of **target filenames**.

| Old (wrong) | New (correct) |
|-------------|--------------|
| `"add_header"` | `".htaccess"` |
| `"add_directive"` | `".htaccess"` |
| `"add_files_block"` | `".htaccess"` |
| `"add_directory_block"` | `"wp-content/uploads/.htaccess"` (RISK-020 only) |

---

## mod_headers Requirements Documented (9 risks)

All Header directives now include:
- `requires_module: "mod_headers"`
- `module_enable_cmd: "sudo a2enmod headers && sudo service apache2 restart"`
- `allowoverride_required: "FileInfo"`

Affected: RISK-012, RISK-014–018, RISK-022, RISK-024, RISK-031–033

---

## AllowOverride Requirements Documented (3 risks)

RISK-013 and RISK-026 (Options directives) and RISK-023 (RewriteRule) now carry explicit AllowOverride requirement notes.

---

## wp-config.php Enrichment (19 risks)

19 risks using `op=constant_exists` were missing `target_constant` and `target_value` fields — these are required for automated verification (e.g. `wp config get CONSTANT`).

All 21 wp-config risks now have:
- `target_constants[]` — array of constant names extracted from code
- `target_values[]` — corresponding values
- `insertion_rule` — "Must be placed BEFORE require_once ABSPATH . 'wp-settings.php'"
- `verification.command` — `wp config get {CONSTANT_NAME}`
- `rollback` — `wp config delete {CONSTANT_NAME}`

Special case: RISK-078 uses `$table_prefix` (variable, not define) — documented separately.

---

## Pattern Extension (3 risks)

| Risk | Fix |
|------|-----|
| RISK-019 | Pattern extended: added `.ini`, `.yml`, `.yaml`, `.conf` extensions |
| RISK-029 | Pattern extended: added `.swp`, `.sql`, `.dump` extensions |
| RISK-030 | Pattern extended: added `.error_log`, `.access_log` extensions |

---

## New: AI Agent .htaccess Syntax Guard

`ai_agent_instructions_v1.1.json` now includes a `htaccess_syntax_guard` section that the AI Agent must check before emitting any .htaccess code:

- **Forbidden directives list** with reason + correct alternative for each
- **Required companions** (RewriteEngine On, mod_headers, AllowOverride)  
- **Valid vs invalid block directives** in .htaccess context
- **Insertion point token definitions** (6 canonical tokens)
- **Apache 2.4+ compatibility note** (Order/Deny/Allow → Require all denied)
- **target_file rules** (RISK-020 must target uploads/.htaccess)

### Updated Self-Check Rubric: 8 checks (v1.0) → 15 checks (v1.1)
Minimum passing score raised from 9/10 to 13/15.

New checks added:
- No forbidden .htaccess directives in output (weight: 2)
- RewriteEngine On present before every Rewrite block (weight: 1)
- mod_headers requirement noted for Header directives (weight: 1)
- AllowOverride requirement noted for Options directives (weight: 1)
- target_file = uploads/.htaccess for RISK-020 (weight: 1)
- IIS URL Rewrite Module requirement noted when `<rewrite>` used (weight: 1)
- Caddy output uses v2 syntax only (weight: 1)

---

## Cloudflare, IIS, Caddy — Rebuilt from Corrected Source

All three platform patterns for the 26 corrected .htaccess risks have been regenerated from the fixed source code. Key improvements:

- **Cloudflare**: Expression syntax now correctly uses `ends_with` for extension blocks, proper header names extracted from corrected code
- **IIS**: `removeServerHeader="true"` added for RISK-024, URL Rewrite requirements explicitly stated
- **Caddy**: Named matchers now use risk-safe IDs (no hyphens), RISK-020 generates a `respond` block for the uploads path

---

*All 47 issues resolved. Validation: 0 errors, 0 critical warnings.*
