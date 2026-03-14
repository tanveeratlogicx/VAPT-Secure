# Implementation Plan - Complete Reset State Purge

This plan addresses the requirement to ensure a "Complete Clean Look" when a feature is reset to "Draft" status (Wipe Data). The goal is to physically remove any rule/code blocks from the `.htaccess`, `wp-config.php`, and `vapt-functions.php` files.

## User Review Required

> [!IMPORTANT]
> This change will trigger a full rebuild of configuration files whenever a feature is reset. This is a safe operation but may momentarily regenerate files.
>
> [!NOTE]
> `vapt-functions.php` will be automatically purged of markers that do not correspond to active features.

## Proposed Changes

### [Backend Workflow & Enforcer]

Summary: Integrate the "Purge" logic into the feature transition process and enhance the global rebuild mechanism.

---

#### [MODIFY] [class-vaptsecure-workflow.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/class-vaptsecure-workflow.php)

- Update `transition_feature()` to trigger `VAPTSECURE_Enforcer::dispatch_enforcement()` and `VAPTSECURE_Enforcer::rebuild_all()` when the new status is `Draft`.
- This ensures that both adaptive blocks and systemic rules are purged immediately.

#### [MODIFY] [class-vaptsecure-enforcer.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/class-vaptsecure-enforcer.php)

- Add `rebuild_php_functions()` method to handle purging of blocks from `vapt-functions.php` (local and plugin versions).
- Enhance `rebuild_all()` to call `rebuild_php_functions()`.
- Ensure `VAPTSECURE_Enforcer::rebuild_all()` covers all active drivers identified in the Driver Manifest:
    - Root and Uploads `.htaccess` (Apache/Litespeed)
    - `wp-config.php` (WordPress Constants)
    - `vapt-functions.php` (Custom PHP Logic)
    - `vapt-security.conf` (Nginx - if writable)
    - `web.config` (IIS)
    - `Caddyfile` (Caddy)
    - `jail.local` (Fail2Ban - if writable)
- Ensure `dispatch_enforcement()` correctly handles the transition to a non-enforced state by triggering `undeploy()` in adaptive deployers.

---

### [Housekeeping]

#### [MODIFY] [vapt-debug.txt](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/vapt-debug.txt)

- [DONE] Cleared at the start of the session.

## Verification Plan

### Automated Tests
- Run JS-based `Reset to Draft` for a deployed feature and verify:
  1. `.htaccess` no longer contains the feature block.
  2. `wp-config.php` no longer contains the constant definition.
  3. `vapt-functions.php` (if exists) has its markers removed.
  4. `vapt_deployment_history` shows the change.

### Manual Verification
- Manually check the root `.htaccess` file after a "Wipe Data" action to confirm it only contains the Global Whitelist and WordPress core rules.
- Verify that `vapt-debug.txt` remains clean until fresh actions are performed.

## Revision History
| Date | Time (GMT+5) | Action |
|------|--------------|--------|
| 2026-03-15 | 00:01 | Created implementation plan for Complete Reset Purge. |
