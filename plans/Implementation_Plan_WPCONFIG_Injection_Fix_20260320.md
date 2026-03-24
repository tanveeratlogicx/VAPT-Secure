# Implementation Plan: Investigating wp-config.php Injection Failure

## Status: Initiated (20260320_@0105)

### Latest Comments/Suggestions
*   **20260320_@0505**: **USER FEEDBACK INCORPORATED**: Addressing the case where WordPress is installed in its own directory (e.g., `/wp/`) while `wp-config.php` remains in the root. The deployer will now recursively check parent directories more robustly.
*   **20260320_@0438**: **CRITICAL FINDING**: The Workbench UI (via `GeneratedInterface`) updates `implementation_data` in the database, but it fails to update the top-level `is_enabled` column. Since the backend enforcers strictly check `is_enabled = 1`, no injection occurs even if the UI says "ACTIVE". Plan updated to fix this mapping in `VAPTSECURE_REST::update_feature`.
*   **20260320_@0433**: Investigation shifted to frontend-backend property mapping. Diagnostic script showed `is_enabled: 0` despite UI showing "ACTIVE". Checking `generated-interface.js` for `feat_enabled` vs `is_enabled` discrepancy.

---

## 1. Problem Identification
*   **Issue**: `wp-config.php` is not being updated with VAPT protections (e.g., RISK-001) despite the UI indicating success.
*   **Symptom**: Local system seems "protected" (maybe cached or handled by hooks?) but production fails.
*   **Discrepancy**: UI says "ACTIVE & INJECTED" based on toggles, but `is_enabled` column is 0, causing backend skip.

## 2. Investigation Progress (20260320_@1500 +5GMT)

I have analyzed the core logic and identified several critical issues causing the `wp-config.php` injection failure:

1.  **Restrictive Deployer Check**: `VAPTSECURE_Config_Deployer::can_deploy()` only checks `ABSPATH . 'wp-config.php'`. In many production environments, `wp-config.php` is moved to the parent directory or WordPress is installed in a subdirectory (e.g. `/wp/`).
2.  **Orchestrator Targeting Logic**: `VAPTSECURE_Deployment_Orchestrator` needs to ensure `wp_config` targets are always included if applicable.
3.  **UI Feedback Gap**: The UI shows "ACTIVE" based on `feat_enabled` in JSON, but doesn't set `is_enabled` in DB.
4.  **Local Masking**: `vapt-interceptor-safe.php` is likely providing protection manually on local, masking the injection failure.

## 3. Implementation Tasks

### Phase 1: Fixing Path Resolution (20260320_@0505)
- [ ] Update `VAPTSECURE_Config_Deployer::can_deploy()` to be more aggressive in finding `wp-config.php`.
- [ ] Account for "WordPress in its own directory" scenarios where `ABSPATH` is a subdirectory but `wp-config.php` is in the root.
- [ ] Verification: Test on local by moving `wp-config.php` to parent and ensuring detection still works.

### Phase 2: Orchestrator Targeting Fix
- [ ] Modify `VAPTSECURE_Deployment_Orchestrator::resolve_targets()` to ensure that `wp_config` platforms are always included if they are the primary enforcer for a risk.

### Phase 3: Enhanced Logging
- [ ] Add explicit logging to `VAPTSECURE_Config_Driver::write_batch` and `VAPTSECURE_Enforcer::rebuild_config`.

### Phase 4: Property Mapping Fix (20260320_@0438)
- [ ] Modify `VAPTSECURE_REST::update_feature` to auto-sync `is_enabled` column from `implementation_data` toggles.

## 4. Revision History
| Timestamp | Action | Result |
|-----------|--------|--------|
| 20260320_@0505 | Feedback Loop | Added subdir WP support to path resolution |
| 20260320_@0438 | Logic Update | Targeted REST API for property sync |
| 20260320_@0433 | Property Mapping Check | Added Phase 4 for mapping investigation |
| 20260320_@1500 | Updated Investigation | Identified Orchestrator & Deployer bugs |
| 20260320_@0105 | Initialized Plan | Plan created for investigation |
