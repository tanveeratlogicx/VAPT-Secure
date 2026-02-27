# Implementation Plan: Global Workflow Logic & AJAX Dynamic Update Fix

## User Comments & Feedback
>
> [!IMPORTANT]
> **USER**: Features should be Pushed to Workbench only once it has been deployed from "Design Implementation" Modal.
> Transitioning to Develop from Draft should move it to readiness (Blue button) but NOT push to Workbench yet.
>
> ---

### Latest Technical Updates (20260227_@1845)

1. **Workbench Visibility Gating (is_pushed)**:
   - **Problem**: 'Develop' status features appear in the Workbench immediately after Step 1.
   - **Solution**: Introduce an `is_pushed` flag. Step 1 sets `is_pushed: 0`. Step 2 (Deploy) sets `is_pushed: 1`. Workbench filters for `is_pushed: 1`.

2. **Database Schema Update**:
   - Add `is_pushed` column to `vaptsecure_feature_meta` table.
   - Update `class-vaptsecure-db.php` to handle the new field.

3. **AJAX State Sync Fix**:
   - Update `admin.js` to ensure optimistic updates match correctly on both key and id.

---

## Revision History

### [Current] 20260227_@1845 - Visibility Gating (is_pushed)

- **Goal**: Prevent premature feature push to Workbench.
- **Change**: Added `is_pushed` gating across DB, REST, and UI.

### 20260227_@1200 - AJAX Fix & Sync Cron Removal

- **Goal**: Restore manual control over the workflow and fix the UI dynamic updates.
- **Change**: Deleted `VAPTSECURE_Sync_Cron`. Fixed `admin.js` for Step 1/Step 2 separation.

---

## Proposed Changes

### [MODIFY] vaptsecure.php (Plugin Core)

- Add `is_pushed` TINYINT(1) DEFAULT 0 to DB schema.
- Add migration to flag existing 'Release' features as pushed.

### [MODIFY] class-vaptsecure-db.php (DB Helper)

- Add `is_pushed` to `$schema_map`.

### [MODIFY] admin.js (JS Component)

- `confirmTransition` (Step 1): Set `is_pushed: 0`.
- `handleSave` (Step 2 - Deploy): Set `is_pushed: 1`.

### [MODIFY] class-vaptsecure-rest.php (REST API)

- Filter `scope=client` results by `is_pushed` for develop/test features.

### [MODIFY] workbench.js & client.js (Workbench UI)

- Respect `is_pushed` flag in feature filtering.

---

## Verification Plan

### Manual Verification

1. Transition a feature from **Draft** to **Develop** -> Verify it is NOT in Workbench.
2. Click **Deploy** in Design Modal -> Verify it IS now in Workbench.
3. Verify rules are written correctly based on enforcement toggle.
