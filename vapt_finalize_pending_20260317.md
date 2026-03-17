# Implementation Plan: Finalizing Persistent TODOs

## Status: In Progress (20260317_@0745)

### Latest Comments/Suggestions
*   **20260317_@0745**: Initiated cleanup of pending items from previous conversation. Focus: REST strategy audit, Absolute paths in UI, and localized setting enhancement.

---

## 1. Problem Identification
*   **REST Audit**: `VAPTSECURE_REST::analyze_enforcement_strategy` needs verification for multi-server support (potential narrow focus on `.htaccess` / `wp-config`). [FIXED]
*   **UI Path Disclosure**: The UI currently shows relative paths (`./wp-config.php`). The user requested absolute filesystem paths for physical verification. [FIXED]
*   **Localized Context**: Frontend lacks `VAPTSECURE_PATH` and `uploadsPath` in localized settings, making it impossible to show absolute paths in the UI. [FIXED]
*   **Tooltip Visuals**: Verify and ensure "INJECTED" (Green) and "REMOVED" (Red) headers are working correctly in tooltips. [FIXED]

## 2. Technical Implementation

### 2.1 REST Strategy Enhancement
*   [x] **Modify `class-vaptsecure-rest.php`**: Update `analyze_enforcement_strategy` to recognize `nginx`, `iis`, and `caddy` mappings when deciding to switch from `hook` driver.

### 2.2 Localized Settings
*   [x] **Modify `vaptsecure.php`**: Add `vaptPath` and `uploadsPath` to `vaptSecureSettings` localization. (Verified existing `abspath`, `pluginPath`, `uploadPath`)

### 2.3 UI Absolute Paths & Styling
*   [x] **Modify `generated-interface.js`**:
    *   Inject absolute paths into the Tooltip footer using localized settings.
    *   Ensure "INJECTED" / "REMOVED" status headers are bold and color-coded.
    *   Verify Multi-Server Target Resolution (.htaccess vs web.config vs nginx.conf).

## 3. Files Modified
*   `includes/class-vaptsecure-rest.php`
*   `vaptsecure.php`
*   `assets/js/modules/generated-interface.js`

## 4. Revision History
| Timestamp | Action | Result |
|-----------|--------|--------|
| 20260317_@0745 | Plan Creation | Initialized task for pending TODOs |
| 20260317_@0758 | Finalization | Bumped version to 2.5.3 and pushed to GitHub |
