# Implementation Plan - Fix Cache and Enhance Revert

## Revision History

### 20260315_@1410 (+5GMT)

- Refined UI: Removed "Include Release" toggle from dashboard header (kept in modal only).
- Ajaxified Modal: Toggling "Include Release" now automatically refreshes the preview data.

### 20260315_@1402 (+5GMT)

- Updated plan based on user feedback: Simplified "Revert All" enhancement to use a checkbox for including Release status features instead of individual picking.
- Maintenance of the "Hard Delete" requirement for complete removal.

### 20260315_@1358 (+5GMT)

- Initial plan created to address "Clear Cache" route missing and "Revert All" enhancement.
- Added "picking" logic for individual feature selection in batch revert.
- Added hard delete requirement for "complete removal" of data.

---

This plan addresses the "No route found" error for the "Clear Cache" button and enhances the "Revert All to Draft" functionality to include individual feature selection and complete database/file cleanup.

## User Review Required

> [!IMPORTANT]
> The "Revert to Draft" operation will now perform a **hard delete** of feature implementation data and history from the database to ensure "complete removal" as requested. This is irreversible.

## Proposed Changes

### [Component] REST API Layer

#### [MODIFY] [class-vaptsecure-rest.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/class-vaptsecure-rest.php)

- Register the `/clear-cache` POST route as it was missing from `register_routes`.
- Update `batch_revert_to_draft` to accept an optional `feature_keys` array for literal "picking" of features.

---

### [Component] Workflow Engine

#### [MODIFY] [class-vaptsecure-workflow.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/class-vaptsecure-workflow.php)

- Enhance `transition_feature` to delete rows from `vaptsecure_feature_meta` and `vaptsecure_feature_history` when the target status is 'Draft'.
- Update `batch_revert_to_draft` to support a specific list of feature keys.

---

### [Component] Admin Interface

#### [MODIFY] [admin.js](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/js/admin.js)

- Remove the "Include Release" checkbox from the dashboard header (per user request).
- Update `previewBatchRevert` to accept parameter overrides for immediate "Ajaxified" refresh.
- Update `BatchRevertModal` to trigger a refresh instantly when toggling the "Include Release" switch.

---

## Verification Plan

### Automated Tests

- No automated PHP tests are currently available. I will perform manual verification of the REST endpoints using browser inspection.

### Manual Verification

1.  **Clear Cache Test**: Click "Clear Cache" and verify the success toast appears without the "No route found" error.
2.  **Revert UI Test**: Click "Revert All to Draft".
    - Verify NO toggle is present in the dashboard header.
    - Inside the modal, flip the "Include Release" toggle.
    - Verify the preview list refreshes automatically (Ajaxified) and shows/hides Release features.
    - Verify database rows for `meta` and `history` are physically deleted.
    - Verify rule blocks are removed from `.htaccess` and `wp-config.php`.
