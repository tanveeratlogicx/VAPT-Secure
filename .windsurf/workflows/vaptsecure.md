---
description: VAPTSecure Plugin - Mandatory AI Agent Workflow
version: 2.6.1
scope: VAPTSecure Plugin Only
---

# VAPTSecure Code Generation Workflow

> **MANDATORY WORKFLOW:** All AI agents working on the VAPTSecure plugin MUST follow this workflow. Violations will result in broken WordPress sites.

---

## Prerequisites

// turbo
1. Verify workspace is within VAPT-Secure plugin directory (`wp-content/plugins/VAPT-Secure/`)
2. Confirm `.ai/VAPTSECURE.md` exists and is accessible
3. Load `data/interface_schema_v2.0.json` into context (reference only)

---

## Phase 1: Pre-Generation Verification (MANDATORY)

// turbo
**BEFORE ANY CODE GENERATION, the agent MUST:**

### Step 1: Read Compliance Document
Read and acknowledge `.ai/VAPTSECURE.md`. This document contains:
- Pre-generation checklist
- Post-generation rubric (19-point, minimum 16/20)
- VAPT_Driver execution reference
- htaccess syntax guard
- Critical safety rules

### Step 2: Answer Verification Questions

The agent MUST answer these 5 questions correctly before proceeding:

**Q1: What is VAPTSecure's core architecture?**
- Expected: "A schema-driven security rule engine with 5-file Unified Bundle v2.0 system"
- Key elements: AI Agent Layer (3 files) + Driver Layer (2 files)

**Q2: Where do enforcement rules come from?**
- Expected: "enforcer_pattern_library_v2.0.json - NEVER from memory"
- lib_key determines syntax: htaccess, wp_config, php_functions, etc.

**Q3: What paths must NEVER be blocked?**
- Expected: "/wp-admin/, /wp-login.php, /wp-json/wp/v2/, /wp-json/vaptsecure/v1/"
- Violation = broken WordPress functionality

**Q4: Where do RewriteRule directives go?**
- Expected: "BEFORE # BEGIN WordPress, wrapped in <IfModule mod_rewrite.c> with RewriteEngine On and RewriteBase /"
- Reason: WordPress's [L] flag creates dead zone after # END WordPress

**Q5: What lib_key should be used for PHP constants?**
- Expected: "wp_config, NOT htaccess"
- htaccess with PHP code causes 500 SERVER ERROR

// turbo
**If any answer is incorrect → STOP and re-read .ai/VAPTSECURE.md**

---

## Phase 2: File Reading (Context Loading)

Based on task type, read required files:

### For UI Generation:
- `data/interface_schema_v2.0.json` → Extract `ui_layout`, `components[]`, `severity`
- `data/enforcer_pattern_library_v2.0.json` → Reference for component styling

### For Enforcement Code Generation:
- `data/enforcer_pattern_library_v2.0.json` → Load `wrapped_code` for lib_key
- `data/interface_schema_v2.0.json` → Check `available_platforms`, `code_ref`
- `data/vapt_driver_manifest_v2.0.json` → Reference for driver fields
- **`data/Enforcers/{lib_key}-template.json`** → Platform-specific risk catalog and templates (htaccess, nginx, wp-config, php-functions, fail2ban, caddy, apache, server-cron, wordpress)

### For Driver Diagnosis:
- `data/vapt_driver_manifest_v2.0.json` → Load steps for risk_id
- `data/enforcer_pattern_library_v2.0.json` → Cross-reference patterns

### For New Risk Entry:
- `data/enforcer_pattern_library_v2.0.json` → Template for all lib_keys
- `data/interface_schema_v2.0.json` → UI structure template
- `data/vapt_driver_manifest_v2.0.json` → Driver manifest template

---

## Phase 3: Code Generation (Implementation)

### 3.1 Follow Naming Conventions

| Element | Format | Example |
|---------|--------|---------|
| Component ID | UI-RISK-{NNN}-{SEQ} | UI-RISK-003-001 |
| Action ID | ACTION-{NNN}-{SEQ} | ACTION-003-001 |
| Toggle Handler | handleRISK{NNN}ToggleChange | handleRISK003ToggleChange |
| Dropdown Handler | handleRISK{NNN}DropdownChange | handleRISK003DropdownChange |
| Settings Key | vapt_risk_{nnn}_enabled | vapt_risk_003_enabled |
| PHP Function | vapt_{descriptive_name} | vapt_disable_xmlrpc |
| Caddy Matcher | @risk{nnn} | @risk003 |

### 3.2 Generate UI Components

1. Read `interface_schema_v2.0.risk_interfaces[risk_id]`
2. Extract `ui_layout`, `components[]`, `actions[]`, `severity.colors`
3. Generate React/JSON component
4. Ensure component IDs match interface_schema exactly

### 3.3 Generate Enforcement Code

1. Read `interface_schema_v2.0.risk_interfaces[risk_id].platform_implementations[platform]`
2. Read `lib_key` from platform_implementations
3. Read `enforcer_pattern_library_v2.0.patterns[risk_id][lib_key]`
4. **If .htaccess:** Run htaccess syntax guard (check forbidden directives)
5. Output `wrapped_code` with VAPT block markers
6. **Payload Syntax Verification:** Ensure code matches enforcer type
   - .htaccess = Apache directives, NOT PHP
   - wp-config.php = PHP constants, NOT Apache
7. Append `verification.command`

### 3.4 htaccess Syntax Guard (CRITICAL)

Before emitting ANY .htaccess code, verify:

#### Forbidden Directives (NEVER use):
- ❌ `TraceEnable` → Use `RewriteCond %{REQUEST_METHOD} ^TRACE [NC]`
- ❌ `ServerSignature` → Use `Header unset Server`
- ❌ `ServerTokens` → Use `Header unset Server`
- ❌ `<Directory>` → Use `<FilesMatch>` in subdirectory .htaccess
- ❌ `<?php`, `define(`, `add_action(` → Use `lib_key: wp_config` or `php_functions`

#### Required Structure:
```apache
# BEGIN VAPT RISK-XXX
# Requires: mod_rewrite | AllowOverride: FileInfo or All
# Position: BEFORE # BEGIN WordPress
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    {your_rules}
</IfModule>
# END VAPT RISK-XXX
```

#### Required Position:
- **BEFORE** `# BEGIN WordPress` (mandatory for RewriteRule)
- **AFTER** `# END WordPress` only for Header/Options (non-rewrite)

---

## Phase 4: Post-Generation Validation (MANDATORY)

// turbo
**After code generation, run the 19-point self-check rubric:**

### Self-Check Score Card

| # | Check | Weight | Status |
|---|-------|--------|--------|
| 1 | Component IDs match interface_schema exactly | 2 | [ ] |
| 2 | Enforcement code from pattern library (not memory) | 2 | [ ] |
| 3 | Severity badge colors match global config | 1 | [ ] |
| 4 | Handler names follow naming conventions | 1 | [ ] |
| 5 | Platform in available_platforms | 1 | [ ] |
| 6 | VAPT block markers present | 1 | [ ] |
| 7 | Verification command present | 1 | [ ] |
| 8 | No forbidden naming patterns | 1 | [ ] |
| 9 | No forbidden htaccess directives | 2 | [ ] |
| 10 | RewriteRule BEFORE # BEGIN WordPress | 1 | [ ] |
| 11 | IfModule wrapper with RewriteEngine On + RewriteBase / | 1 | [ ] |
| 12 | mod_headers requirement noted | 1 | [ ] |
| 13 | AllowOverride requirement noted | 1 | [ ] |
| 14 | RISK-020 target_file correct (uploads/.htaccess) | 1 | [ ] |
| 15 | IIS URL Rewrite Module 2.1 noted | 1 | [ ] |
| 16 | Caddy v2 syntax only | 1 | [ ] |
| 17 | code_ref uses correct lib_key | 1 | [ ] |
| 18 | driver_ref points to manifest | 1 | [ ] |
| 19 | Driver fields complete | 1 | [ ] |
| 20 | Payload syntax matches target file engine | 1 | [ ] |
|   | **TOTAL** | **20** | **[__/20]** |

### Scoring Rules

- Sum weights of passing checks
- **Minimum to deliver: 16/20**
- **Score < 16 → Identify failures, regenerate, re-score**
- **Score ≥ 16 → Proceed to delivery**

### Validation Questions

Before marking complete, verify:

1. **Did I read from pattern library?** (Check #2) - If wrote from memory → FAIL
2. **Are block markers present?** (Check #6) - If missing → FAIL
3. **Is htaccess syntax valid?** (Check #9, #10, #11) - If forbidden directives or wrong position → FAIL
4. **Did I block WordPress core paths?** - If yes → CRITICAL FAIL, regenerate immediately

---

## Phase 5: Delivery

### Delivery Checklist

- [ ] Pre-generation verification completed
- [ ] All required JSON files read
- [ ] Code generated following patterns
- [ ] htaccess syntax guard passed (if applicable)
- [ ] Self-check rubric score documented (__/20)
- [ ] Score ≥ 16/20
- [ ] All failures explained (if score < 20)
- [ ] Code ready for review

### Delivery Format

```
## VAPTSecure Code Delivery

**Task:** [UI Generation / Enforcement Code / Driver Diagnosis / New Risk]
**Risk ID:** [RISK-XXX]
**Platform:** [htaccess/nginx/wp_config/etc.]

### Pre-Generation Verification
- [x] Read .ai/VAPTSECURE.md
- [x] Answered 5 verification questions correctly
- [x] Loaded required JSON files

### Generated Output
[Code output here]

### Post-Generation Validation
**Rubric Score:** __/20 (Minimum: 16)

**Passed Checks:** [List]
**Failed Checks:** [List, with explanation]

**Ready for Review:** [Yes/No]
```

---

## Emergency Procedures

### If Uncertain About Any Rule

1. **STOP** current implementation
2. **Re-read** `.ai/VAPTSECURE.md`
3. **Re-read** `data/ai_agent_instructions_v2.0.json`
4. **Re-read** `data/VAPT_AI_Agent_System_README_v2.0.md`
5. **Restart** from Phase 1

### If Score < 16 on Rubric

1. **Identify** all failing checks
2. **Document** why each check failed
3. **Regenerate** the failing components
4. **Re-run** rubric scoring
5. **Repeat** until score ≥ 16

### If WordPress Core Paths Were Blocked

1. **STOP** immediately
2. **Remove** the blocking rules
3. **Review** `.ai/VAPTSECURE.md` "NEVER Block These Paths" section
4. **Regenerate** with correct exclusions
5. **Verify** paths are accessible in output

---

## Quick Command Reference

### Using This Workflow

When working on VAPTSecure tasks:

1. **Start here** - Read this workflow file first
2. **Follow phases** - Complete each phase before moving to next
3. **Check turbo markers** - Auto-run these steps without prompting
4. **Validate rigorously** - Never skip post-generation rubric

### Cross-Reference Documents

| Document | Purpose |
|----------|---------|
| `.ai/VAPTSECURE.md` | Compliance rules, checklists, reference tables |
| `data/ai_agent_instructions_v2.0.json` | System prompt, task definitions, rubric details |
| `data/interface_schema_v2.0.json` | UI component definitions, risk metadata |
| `data/enforcer_pattern_library_v2.0.json` | Enforcement code for all platforms |
| `data/vapt_driver_manifest_v2.0.json` | Driver layer execution instructions |
| `data/Enforcers/{lib_key}-template.json` | **Platform-specific risk catalogs** (9 templates: htaccess, nginx, wp-config, php-functions, fail2ban, caddy, apache, server-cron, wordpress) |
| `data/VAPT_AI_Agent_System_README_v2.0.md` | Full architecture documentation |
| `data/VAPT_Driver_Reference_v2.0.php` | PHP driver class implementation |

---

## Success Criteria

This workflow is successful when:

- [ ] All code generation follows the 5-phase structure
- [ ] Pre-generation verification is completed for every task
- [ ] Post-generation rubric score is ≥ 16/20
- [ ] No WordPress core paths are blocked
- [ ] All htaccess rules pass syntax guard
- [ ] Naming conventions are followed exactly
- [ ] VAPT block markers are present in all output
- [ ] Pattern library is referenced, not memorized

---

*Workflow Version: 2.6.1*  
*Scope: VAPTSecure Plugin Only*  
*Compliance: .ai/VAPTSECURE.md*
