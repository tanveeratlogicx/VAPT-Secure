# VAPT Risk Catalogue — AI Agent System
## Interface Schema, Enforcer Pattern Library & AI Agent Instructions
**Version:** 1.0.0 | **Generated:** 2026-02-20 | **Source:** VAPT-Risk-Catalogue-Full-125-v3_4_1.json (125 risks)

---

## Overview

This package transforms the VAPT Risk Catalogue into a three-file AI Agent system engineered to achieve **≥90% output accuracy** when generating UI component schemas and platform-specific enforcement code for 125 WordPress security risks.

---

## Deliverable Files

| File | Purpose | Size |
|------|---------|------|
| `interface_schema.json` | Complete UI schema for all 125 risks — component types, layout, actions, props, state shape | ~243 KB |
| `enforcer_pattern_library.json` | Platform-specific enforcement code for all 125 risks across 8+ platforms including Cloudflare, IIS, Caddy | ~157 KB |
| `ai_agent_instructions.json` | Full AI Agent system prompt, task templates, output templates, self-check rubric, example flows | ~49 KB |

---

## Source File Analysis

### Risk Distribution
| Category | Count |
|----------|-------|
| Configuration | 78 |
| Information Disclosure | 25 |
| Authentication | 15 |
| Injection | 5 |
| API Security | 2 |

| Severity | Count |
|----------|-------|
| Critical | 9 |
| High | 36 |
| Medium | 46 |
| Low | 34 |

### Original Enforcer Coverage
| Enforcer | Risks Covered |
|----------|--------------|
| .htaccess (Apache) | 28 |
| PHP Functions | 28 |
| wp-config.php | 21 |
| fail2ban | 16 |
| Server Cron | 10 |
| WordPress | 10 |
| Nginx | 7 |
| Apache | 2 |
| Caddy (native) | 2 |

---

## New Platform Coverage Added

This package adds **Cloudflare**, **IIS**, and **expanded Caddy** enforcement patterns for all 125 risks.

### Cloudflare Patterns
Each Apache/.htaccess rule has been mapped to the appropriate Cloudflare product:

| Implementation Type | Cloudflare Product | Example |
|--------------------|--------------------|---------|
| `transform_rule` | HTTP Response Header Modification | X-Frame-Options, HSTS, CSP headers |
| `waf_custom_rule` | WAF Custom Rules | Block xmlrpc.php, wp-json users, author enumeration |
| `notes_only` | Origin must be configured | Options -Indexes, ServerSignature |

### IIS (web.config) Patterns
| Implementation Type | IIS Config Section | Example |
|--------------------|--------------------|---------|
| `web_config_http_headers` | `<httpProtocol><customHeaders>` | All security headers |
| `web_config_request_filtering` | `<security><requestFiltering>` | Block sensitive files |
| `web_config_url_rewrite` | `<rewrite><rules>` | Author enumeration, REST API blocks |
| `web_config_directory_browsing` | `<directoryBrowse enabled="false" />` | Directory listing |

**Requirement:** IIS URL Rewrite Module must be installed for path-based rules.

### Caddy Patterns
| Implementation Type | Caddyfile Directive | Example |
|--------------------|---------------------|---------|
| `caddy_header` | `header { Name "Value" }` | All security headers, Server header removal |
| `caddy_respond_block` | `@matcher { } respond @matcher 403` | File blocks, path blocks |
| `caddy_file_server` | `file_server { }` | Directory listing disabled |
| `native_caddy` | Direct Caddy directives | RISK-123, RISK-124 (native Caddy risks) |

**Validation:** Always run `caddy validate` before `caddy reload`.

---

## How the AI Agent Should Use These Files

### Step 1 — Select Task Type
The agent supports four task types defined in `ai_agent_instructions.json`:
1. `generate_ui_component_schema` — UI JSON for one risk
2. `generate_enforcement_code` — Platform code for one risk + platform
3. `generate_full_risk_package` — UI + all platforms for one risk
4. `generate_bulk_schema` — All 125 risks (filterable)

### Step 2 — Schema-First Lookup
```
interface_schema.risk_interfaces[RISK-XXX]
  → components[], layout, actions, severity, platform_implementations
```

### Step 3 — Pattern Library Lookup
```
enforcer_pattern_library.patterns[RISK-XXX].cloudflare
enforcer_pattern_library.patterns[RISK-XXX].iis
enforcer_pattern_library.patterns[RISK-XXX].caddy
```

### Step 4 — Apply Enforcer Validation Gate
- Check `implementation_type`
- If `notes_only`: output note + fallback secondary defense rule
- If code type: output from the appropriate field

### Step 5 — Self-Check Rubric (Score ≥9/10 required)
| Check | Weight |
|-------|--------|
| Component IDs match schema exactly | 2 |
| Enforcement code from pattern library | 2 |
| Severity badge colors correct | 1 |
| Handler names follow conventions | 1 |
| Platform in available_platforms | 1 |
| VAPT block markers present | 1 |
| Verification command present | 1 |
| No forbidden patterns violated | 1 |

---

## Enforcer Block Markers (Required in all code output)

### Apache/.htaccess
```apache
# BEGIN VAPT RISK-XXX
<your code>
# END VAPT RISK-XXX
```

### IIS web.config
```xml
<!-- BEGIN VAPT RISK-XXX -->
<your code>
<!-- END VAPT RISK-XXX -->
```

### Caddy
```
# BEGIN VAPT RISK-XXX
<your code>
# END VAPT RISK-XXX
```

### Cloudflare (comment in rule description)
```
VAPT RISK-XXX: [title]
```

### wp-config.php
```php
/* BEGIN VAPT RISK-XXX */
<your code>
/* END VAPT RISK-XXX */
```

---

## Platform Verification Commands

| Platform | Validate | Reload | Test |
|----------|----------|--------|------|
| Apache/.htaccess | `apachectl -t` | `service apache2 reload` | `curl -sI https://site.com` |
| IIS | `appcmd validate config` | `iisreset` | `curl -sI https://site.com` |
| Caddy | `caddy validate` | `caddy reload` | `curl -sI https://site.com` |
| Cloudflare | Dashboard review | Instant on save | `curl -sI https://site.com` |
| Nginx | `nginx -t` | `nginx -s reload` | `curl -sI https://site.com` |
| fail2ban | `fail2ban-client -t` | `fail2ban-client reload` | `fail2ban-client status` |

---

## Key Limitations & Notes

### Cloudflare
- Cannot enforce `Options -Indexes` — this must be set at origin server
- Cannot set `ServerTokens Prod` — strip `Server` header via Transform Rule as complement
- Rate limiting (fail2ban equivalents) requires Cloudflare Pro or higher
- wp-config.php constants cannot be enforced from the edge

### IIS
- URL Rewrite Module is **required** — install via Web Platform Installer or IIS Manager
- PHP engine flag (`php_flag engine off`) has no direct IIS equivalent; use `requestFiltering` to block PHP files in uploads
- `AllowOverride` equivalent does not exist in IIS — all config is in web.config hierarchy

### Caddy
- Uses **Caddyfile v2 syntax only** — do not use v1 directives
- Directory listing is disabled by default — no explicit directive needed unless `browse` was enabled
- For sites using Caddy as reverse proxy to PHP-FPM, some rules apply to the upstream, not Caddy itself

---

## Usage Example

**Prompt to AI Agent:**
```
Generate the full risk package for RISK-022 (Missing Content-Security-Policy Header)
for platforms: .htaccess, Cloudflare, IIS, Caddy
```

**Agent Workflow:**
1. Load `interface_schema.risk_interfaces.RISK-022`
2. Load `enforcer_pattern_library.patterns.RISK-022` for all 4 platforms
3. Generate UI schema from components + layout
4. Output .htaccess: `Header always set Content-Security-Policy "default-src 'self';..."`
5. Output Cloudflare: Transform Rule → HTTP Response Header → Content-Security-Policy
6. Output IIS: `<customHeaders><add name="Content-Security-Policy" value="..." />`
7. Output Caddy: `header { Content-Security-Policy "default-src 'self'..." }`
8. Self-check all outputs → score 10/10 → deliver package

---

*Generated by VAPT Risk Catalogue Transformation System v1.0.0*
