# Implementation Plan - Workflow Enhancement (v2.1)

Enhance VAPT workflows to support the full transition from Draft -> Develop -> Deploy, ensuring alignment with Schema-First Architecture v2.0 and Toggle Intelligence.

## Latest Comments/Suggestions
- **20260312_@2200**: Discovered that `VAPTSECURE_Hook_Driver` defaults `feat_enabled` to `true` if missing, and `VAPTSECURE_Enforcer` was loading features without checking `is_enabled`. Also found an aggressive fallback in `aplus-generator.js` that injects SQL keyword blocks. Implementing a multi-layer fix to ensure proper toggle intelligence.

## Proposed Changes

### [VAPT-Secure](t:\~\Local925 Sites\hermasnet\app\public\wp-content\plugins\VAPT-Secure)

Summary of what will change in this component, separated by files.

#### [MODIFY] [class-vaptsecure-enforcer.php](file:///t%3A/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/class-vaptsecure-enforcer.php)
- Modified `runtime_enforcement()` SQL query to include `AND m.is_enabled = 1`.
- This ensures disabled features are not loaded into memory.

#### [MODIFY] [class-vaptsecure-hook-driver.php](file:///t%3A/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/enforcers/class-vaptsecure-hook-driver.php)
- Changed default `$is_enabled` from `true` to `false` in `apply()`.
- This provides a secondary safety guard against accidental enforcement.

#### [MODIFY] [aplus-generator.js](file:///t%3A/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/js/modules/aplus-generator.js)
- Removed aggressive SQL keyword fallbacks (`concat`, `union`, etc.) that were being injected when remediation was missing.
- Added `/wp-admin/` and REST API whitelisting markers to generated rules.
- Improved verification probes for RISK-003 to include `author=1` check.

## Verification Plan

### Automated Tests
- [x] Ran diagnostic SQL script `check_enforced.php` to verify no disabled features are loaded.
- [x] Verified `vapt-debug.txt` logs confirm "Deactivated" status for disabled features.
- [x] Verified `.htaccess` is clean of aggressive SQL filters.

### Manual Verification
- [ ] User to verify that deleting a user (`action=delete`) no longer triggers a 403 Forbidden error.
- [ ] User to verify that RISK-003 still blocks `/wp-json/wp/v2/users` when ENABLED.

---
**Revision History**
- 20260313_@0045: Bumped version to 2.4.10 and resolved Apache 500 Internal Server error on the REST API caused by a legacy `vapt_security_module` redirect loop in `.htaccess`.
- 20260312_@1845: Implemented core fixes for 403 error (Enforcer, Hook Driver, Generator).
- 20260312_@1433: Initial plan to resolve 403 error.

#### [MODIFY] [class-vaptsecure-nginx-deployer.php](file:///t%3A/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/enforcers/class-vaptsecure-nginx-deployer.php)
-   Update `update_rules_file` to handle line-by-line commenting for disabled states.

#### [MODIFY] [class-vaptsecure-deployment-orchestrator.php](file:///t%3A/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/class-vaptsecure-deployment-orchestrator.php)
-   Clean up redundant comment-out logic in `derive_matrix_from_legacy` to favor Deployer-level enforcement.
-   Ensure all platforms receive the correct `$is_enabled` flag.

## Verification Plan
### Manual Verification
1. **Centralized Whitelist Verification**: Confirm `.htaccess` contains a single `VAPT GLOBAL WHITELIST` block setting the `VAPT_WHITELIST` environment variable.
2. **Variable Reference Check**: Verify that feature-specific rules (e.g., RISK-003) now use `RewriteCond %{ENV:VAPT_WHITELIST} !1` instead of embedding the whitelist.
3. **Toggle Intelligence Verification**: Deploy a feature with "Enable Protection" toggled OFF and verify content is commented out and marker says `- DISABLED`.
4. **PHP Runtime Check**: Confirm that disabling a feature also stops its PHP-level enforcement (no 403 on user deletion).
5. **Probe Accuracy Check**: Verify that the RISK-003 probe accurately reflects the protection state.
6. **Rubric Check**: Confirm final rubric score is **19/19**.
