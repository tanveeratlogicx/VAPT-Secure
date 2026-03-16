# Implementation Plan: Refine Enforcer Selection Logic

**Status**: [PENDING REVIEW]
**Last Updated**: 2026-03-15 15:45 (GMT+5)
**Task ID**: ENFORCER_LOGIC_FIX

---

## Revision History / Changelog

### [20260315_@1550] - User Feedback Integration

- Added requirement to display radio buttons when multiple compatible enforcers are available.
- Selection of enforcer will persist and be used for feature implementation.

### [20260315_@1545] - Initial Plan Creation

- Proposed refining `resolveEnforcer` logic in `assets/js/admin.js`.
- Defined compatibility mapping between environment capabilities and enforcer names.
- Added filtering to exclude incompatible enforcers (e.g., Nginx on Apache).

---

## Latest Progress / Comments
- Research completed on `VAPTSECURE_Environment_Detector` (PHP) and `resolveEnforcer` (JS).
- Identified that current logic falls back to the first available enforcer regardless of environment compatibility.
- Plan drafted to use `environmentProfile.capabilities` for strict filtering.

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

2.  **Verify Radio Button Selection**:
    - Check features with multiple compatible enforcers (e.g., `.htaccess` and `PHP Functions`).
    - Verify radio buttons are displayed.
    - Select one and verify it persists on refresh (via `updateFeature` call).

3.  **Verify Implementation Respects Selection**:
    - Select an enforcer via the radio buttons.
    - Click "Design" -> "Implement/Deploy".
    - Verify that the generated schema/rule target matches the selected enforcer.
