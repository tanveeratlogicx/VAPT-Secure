# Implementation Plan: Refine Enforcer Selection Logic

**Status**: [PENDING REVIEW]
**Last Updated**: 2026-03-15 15:45 (GMT+5)
**Task ID**: ENFORCER_LOGIC_FIX

---

## Revision History / Changelog

### [20260316_@0655] - RISK-010 Apache Enforcer Fix
- **FIXED**: Added missing `.htaccess` enforcer to `RISK-010` in `enforcer_pattern_library_v2.0.json`, `vapt_driver_manifest_v2.0.json`, and `interface_schema_v2.0.json`.
- **IMPROVED**: Added `x-vapt-enforced` and `x-vapt-risk-id` headers to `RISK-010` enforcer blocks (both Nginx and Apache) to satisfy A+ Automated Verification.
- **OPTIMIZED**: Reordered enforcer steps in the manifest to prioritize `.htaccess` over Nginx for Apache environments.
- **VERIFIED**: Domain Admin column now correctly reflects `.htaccess` for RISK-010 on Apache.

### [20260315_@2200] - Persistence & Visibility Finalized
- **FIXED**: `matches_criterion` in `VAPTSECURE_Environment_Detector` now correctly handles `php_sapi_detection:any`, enabling `fail2ban` and `server_cron` visibility.
- **STRENGTHENED**: Added explicit guard in `build_capability_profile` to suppress IIS if Apache/Nginx capabilities are already matched.
- **VERIFIED**: Browser check confirmed `fail2ban` visibility for RISK-007 and complete removal of IIS for Apache-specific risks.

### [20260315_@1905] - Critical Fix: Dashboard Loading
- Resolved "Stuck at Loading" issue caused by a syntax error in `generateDevInstructions`.
- Re-verified full dashboard functionality on Nginx.

### [20260315_@1605] - Final Implementation Complete
- Fixed `resolveEnforcer` to strictly check boolean capability status.
- Updated `generateDevInstructions` to respect `active_enforcer` selection.
- Verified backend persistence for `active_enforcer`.
- Display of radio buttons for multiple compatible enforcers confirmed.

### [20260315_@1550] - User Feedback Integration
- Added requirement to display radio buttons when multiple compatible enforcers are available.
- Selection of enforcer will persist and be used for feature implementation.

### [20260315_@1545] - Initial Plan Creation
- Proposed refining `resolveEnforcer` logic in `assets/js/admin.js`.
- Defined compatibility mapping between environment capabilities and enforcer names.
- Added filtering to exclude incompatible enforcers (e.g., Nginx on Apache).

### [20260316_@1500] - Option B: Standardize Datafile Structures
- **STANDARDIZED**: Merged inconsistent enforcer names (`WordPress Core`, `WordPress`) into `PHP Functions` across `interface_schema_v2.0.json`, `enforcer_pattern_library_v2.0.json`, and `vapt_driver_manifest_v2.0.json`.
- **STANDARDIZED**: Resolved `caddy_native` to `Caddy` natively within the schemas.
- **AUDITED**: Re-ran audit script to verify 0 Features with Missing/Empty Enforcers! All 36+ entries correctly map to their standard environment capabilities.
- **COMMITTED**: Bumped plugin version to `2.4.25` prior to adjustments, and committed final Option B logic.

---

## Latest Progress / Comments
- **RESOLVED**: Option B complete! Adjusted the Datafiles Structures to use standard references (`PHP Functions`, `Caddy`). No more missing enforcers in the backend schema logic.
- **RESOLVED**: Fixed RISK-010 incorrectly using Nginx on Apache by adding the missing `.htaccess` platform implementation and manifest steps.
- **RESOLVED**: Added A+ Adaptive headers to RISK-010 enforcer blocks to fix verification failures.
- **STATUS**: The Datafiles are fully aligned with the optimal platforms identified by `class-vaptsecure-environment-detector.php`.

---

## Goal Description
Ensure the "Enforcer" column in the Features List accurately reflects enforcers that are compatible with the user's active webserver and environment. Prevent showing irrelevant enforcers (like Nginx rules on an Apache server).

## User Review Required

> [!IMPORTANT]
> If a feature *only* supports enforcers that are incompatible with the detected environment (e.g., a feature with only Nginx rules on an Apache server), the column will now show "-" (N/A).
>
> [!TIP]
> **If multiple compatible enforcers exist (e.g., .htaccess and PHP Functions), they will be displayed as a radio button group within the "Enforcer" column.**
> Selecting a radio button will update the feature's `active_enforcer` state, which will be honored during the Implementation/Deployment phase.

## Proposed Changes

### Frontend (JS)

#### [MODIFY] [assets/js/admin.js](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/js/admin.js)

- **resolveEnforcer helper function**:
    - Update logic to filter `platform_implementations` and `steps`.
    - Use `environmentProfile.capabilities` as the source of truth for supported platforms.
    - Map capability keys to enforcer labels.
- **Enforcer Column Rendering**:
    - If multiple compatible enforcers exist, render `RadioControl` (or a styled radio group) for selection.
    - If only one compatible enforcer exists, render it as a label.
    - Handle `onChange` to update the feature's `implementation_enforcer` preference via `updateFeature`.
- **Implementation Logic Integration**:
    - Update the "Implement" and "Deploy" trigger logic (e.g., in `DesignModal` or wherever enforcement is triggered) to prioritize the user-selected enforcer if available.

## Verification Plan

### Automated Tests
- None available for this specific UI logic.

### Manual Verification
1.  **Verify Compatibility Filtering**:
    - On an Apache server (detected via `environmentProfile`), check features that have multiple platform implementations.
    - Verify that only Apache-compatible enforcers (.htaccess) are shown.
    - Check if Nginx-specific enforcers are hidden/replaced by "-" if no Apache alternative exists.
2.  **Verify Optimal Selection**:
    - Ensure that if both `.htaccess` and `php_functions` are available, and `.htaccess` is the optimal platform, it is correctly selected.
3.  **Check Table UI**:
    - Verify the "Enforcer" column still sorts correctly after filtering.
    - Open "Configure Table Columns" and verify "Enforcer" is still available.
