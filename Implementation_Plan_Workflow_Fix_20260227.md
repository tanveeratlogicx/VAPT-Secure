# Implementation Plan: Fix Enforcement & Catalog Sync

## User Comments & Feedback
>
> [!IMPORTANT]
> **USER**: Please add your comments or requested changes below this line. I will review and update the plan automatically once you save the file or send them in chat.
>
> ---

### [Current] 20260227_@1200 - AJAX Fix & Button Color Isolation

- **Issue 1: AJAX Sync**: The `updateFeature` optimistic update fails to trigger because of key mismatch (`f.key` vs `f.id`).
- **Issue 2: Color Logic**: Button turns green at Step 1 because it checks `has_history` instead of `is_enforced`.
- **Issue 3: Step 1 Pre-enforcement**: Some rules (htaccess/config) were being auto-enforced during transition.

**Proposed Fixes**:

1. Update `updateFeature` in `admin.js` to handle both `key` and `id` for optimistic updates.
2. Update the A+ Workbench button color logic:
   - **Solid Blue**: `status === 'Develop' && has_history && !is_enforced`
   - **Solid Green**: `status === 'Develop' && is_enforced`
   - **Solid Orange**: `status === 'Release'`
   - **Gradient Blue**: Default (Draft)
3. Ensure `confirmTransition` does NOT set `is_enforced: 1`.

---

1. Isolate 'Develop' Status:
even though this is a readiness marker, but still visually the state of the feature changes to 'Develop' State and nothing else is done [I am not sure how this state is being handled in the backend - but it's not pused to the Workbech at this state], and the "A+ Workbench" button is displayed in "Blue" Color - Visually representing its Readiness.

2. Gated Enforcement: I didn't get it at all, what you mean by this? (Note: Gated refers to the Step 2 Deploy requirement).

3. Deploy Action (Step 2):
now when the "A+ Workbech" button is clicked and then "Deploy" is clicked, the feature is pushed to the Workbench and the rules are written.

## Revision History

### 20260227_@1145 - Two-Step Workflow Correction

- **Step 1: Readiness (Confirm to Develop)**: Transitions status to `Develop`. Current button state is **Blue**. The feature is NOT pushed to Workbench (`is_enforced = 0`). **No rules are written.**
- **Step 2: Deployment (Deploy)**: Clicked from A+ Workbench modal. The status **REMAINS** `Develop`. Button state turns **Green**. This action pushes rules to Workbench (`is_enforced = 1`) and configuration files.
- **Root Cause**: My previous sync fix was forcing `is_enforced = 1` for any feature in `Develop` status, essentially merging Step 1 and Step 2. I have fixed this in `VAPTSECURE_Sync_Cron`.

### 20260227_@1130 - Two-Step Workflow Refinement (Awaiting Correction)

- **Status**: Incorrectly assumed Step 2 transitioned to 'Release'. Corrected above.

### 20260227_@1115 - Two-Step Implementation Workflow (Initial Proposal)

- **Goal**: Separate Readiness (Step 1) from Deployment (Step 2).
- **Step 1 (Develop)**: Transition feature to 'Develop' status. Strictly **Read-Only**.
- **Step 2 (Deploy)**: Transition to active state (Release) and trigger rule writing.

### 20260227_@1050 - Standby for User Verification

- **Status**: Standby. Awaiting user feedback after manual .htaccess cleanup.
- **Preparations**: Logic is local-ready to respond to feature lifecycle changes (Draft -> Release).

### 20260227_@0525 - Automated Sync & Robust Enforcement

- **Implemented Phoenix Sync v3.0**: Robust deep-sync of 125 risks.
- **Fixed Config Driver**: Resolved `wp_config` key mapping issue in `VAPTSECURE_Enforcer`.
- **Scheduled Synchronization**: Implemented `VAPTSECURE_Sync_Cron` for daily catalog maintenance.
- **Sanitized Config**: Deduplicated rules in `wp-config.php`.

### 20260227_@0405 - Initial Phoenix Sync

- **Populated DB**: Initial sync of 125 risks via `/tmp/phoenix_sync.php`.
- **Enforcement Push**: Triggered global rebuild.
- **Debug Logging**: Added tracing to drivers.

---

## Goal

Restore protection for all 125 risks and automate consistency checks.

## Proposed Changes

### VAPTSECURE_Enforcer

- **[KEEP]** `get_enforced_features`: Maintain existing logic that filters by `is_enforced = 1`. This correctly allows `Develop` features to remain inactive until "Deployed".
- **[MODIFY]** `extract_code_from_mapping`: Fixed platform key resolution (e.g., `wp_config` handled correctly).
- **[MODIFY]** `resolve_impl`/`resolve_schema`: Standardized case-insensitivity for lifecycle states.

### VAPTSECURE_Sync_Cron

- **[MODIFY]** `run_sync`: **CRITICAL FIX**. Remove logic that forces `is_enforced = 1` for `Develop` status. Instead, leave the `is_enforced` flag untouched for `Develop` features to preserve the user's manual "Deploy" state. Only force `is_enforced = 1` for features explicitly in `Release` status.

### VAPTSECURE_Deployment_Orchestrator

- **[KEEP]** `orchestrate`: Ensure it respects the caller's intent and doesn't add ad-hoc status gates that block the `Develop` state.

### VAPTSECURE_Sync_Cron [NEW]

- **[NEW]** `includes/class-vaptsecure-sync-cron.php`: Handles daily catalog-to-workbench alignment.

### VAPTSECURE_Config_Driver

- **[MODIFY]** `generate_rules`: Added rule deduplication.

## Verification Plan

### Automated Tests

- `wp eval "VAPTSECURE_Sync_Cron::run_sync();"`: Validates end-to-end sync.

### Manual Verification

- Inspect `.htaccess` and `wp-config.php` for VAPT markers.
- Check Workbench UI for 125 populated risks.
