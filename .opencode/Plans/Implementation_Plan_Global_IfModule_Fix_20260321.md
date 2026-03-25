# Implementation Plan: Global Fix — Missing `<IfModule mod_headers.c>` Wrapper

**Scope expanded from user feedback**: Rather than fixing only RISK-012, this fix applies globally to all Header directives across all RISKs in the pattern library and enforcer pipeline.

## Root Cause

The `RISK-012` `.htaccess` rule was missing the `<IfModule mod_headers.c>` wrapper. This is a global problem — any `Header` directive injected without this wrapper can cause a `500 Internal Server Error` if Apache's `mod_headers` module is not loaded.

The injection path is via `class-vaptsecure-apache-deployer.php` which reads the `code`/`rules` fields from the pattern library and writes them verbatim.

## Changes Applied

### Layer 1 — Pattern Library (Source Data)
#### [MODIFY] enforcer_pattern_library_v2.0.json
- **33 bare Header entries** across **11 RISKs** updated to include `<IfModule mod_headers.c>` wrapper
- Affected: RISK-012, RISK-014, RISK-015, RISK-022, RISK-024, RISK-031, RISK-032, RISK-033 (+ 3 more)
- Fields patched: `code`, `wrapped_code`, `write_block`

---

### Layer 2 — Live Server Config
#### [MODIFY] .htaccess
- RISK-012 rule immediately wrapped in `<IfModule mod_headers.c>`

---

### Layer 3 — Enforcer (Safety Net)
#### [MODIFY] class-vaptsecure-apache-deployer.php
- Added `ensure_ifmodule_header_wrapper()` method
- Called from `extract_rules()` — ensures all future deployments auto-wrap bare Header directives even if the library entry is missing the wrapper

---

## Revision History

- **20260321_@0033**: Saved copy to `.opencode/Plans/` folder
- **20260321_@0027**: Scope expanded to global fix per user feedback
- **20260321_@0020**: Initial draft — RISK-012 only
