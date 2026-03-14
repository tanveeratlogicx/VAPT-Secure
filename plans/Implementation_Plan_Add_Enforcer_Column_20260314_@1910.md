# Implementation Plan: Add Enforcer Column

**Status**: [PENDING REVIEW]
**Last Updated**: 2026-03-14 19:10 (GMT+5)
**Task ID**: Add_Enforcer_Column

---

## Revision History / Changelog

### [20260314_@1910] - Initial Plan Creation
- Proposed adding "Enforcer" column to the Features List.
- Identified the need for backend environment detection in the REST API.
- Outlined frontend React changes for dynamic mapping.

---

## Latest Progress / Comments
- Research completed on `VAPTSECURE_Environment_Detector` and `FeatureList` rendering.
- Plan drafted to integrate `optimal_platform` from backend to frontend.
- Ready for implementation upon approval.

---

## Goal Description
Add a new "Enforcer" column to the "Features List" tab that dynamically displays the enforcer name (e.g., .htaccess, wp-config.php) based on the detected Server Architecture.

## User Review Required

> [!IMPORTANT]
> The enforcer name will be determined by the "Optimal Platform" detected by the plugin's environment detector. If a feature does not support the optimal platform, it will fallback to displaying the best available platform for that specific risk.

## Proposed Changes

### Backend (PHP)

#### [MODIFY] [includes/class-vaptsecure-rest.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/class-vaptsecure-rest.php)

- Update `get_features` to include the `environment_profile` in the JSON response. This profile contains the detected `optimal_platform`.

### Frontend (JS)

#### [MODIFY] [assets/js/admin.js](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/js/admin.js)

- **VAPTAdmin Component**:
    - Add state for `environmentProfile`.
    - Update `fetchData` to store `res.environment_profile` in state.
    - Pass `environmentProfile` to the `FeatureList` component.
- **FeatureList Component**:
    - Add `enforcer` to the logic that calculates `allKeys`.
    - Update the table rendering:
        - Add "Enforcer" to the sortable columns list.
        - Implement a helper to resolve the enforcer name for a feature:
            - Compare the feature's `available_platforms` with the global `optimal_platform`.
            - Display the most relevant enforcer name (e.g., ".htaccess" for `apache_htaccess`, "PHP Functions" for `php_functions`).
- **Configure Columns Modal**:
    - Ensure "Enforcer" appears in the "Available Fields" list if not already active.

## Verification Plan

### Manual Verification
1.  **Check Column Visibility**:
    - Open the "Configure Table Columns" modal.
    - Verify "Enforcer" is in the list.
    - Toggle it on and verify it appears in the table.
2.  **Verify Data Accuracy**:
    - Check features like `RISK-001` (wp-cron) and verify it shows "wp-config.php".
    - Check features like `RISK-002` (xmlrpc) and verify it shows ".htaccess" (assuming Apache server).
3.  **Check Persistence**:
    - Reorder the "Enforcer" column.
    - Refresh the page and verify the order is preserved.
4.  **Sorting**:
    - Click the "Enforcer" column header.
    - Verify the table sorts correctly by enforcer name.
