# Implementation Plan: Investigating Tooltip Accuracy

**Task Name:** Investigating Tooltip Accuracy
**Date Started:** 2026-03-15
**Latest Comments/Suggestions:**

* Ensure the "Enable Protection" tooltip accurately reflects the underlying enforcement mechanism (Hook Driver vs. wp-config.php).
* Show that wp-config is the primary driver but PHP Hook Driver is the Adaptive Fallback (Superadmin ONLY).
* **HIDE** all Adaptive Fallback technical hooks from standard WordPress Admins to ensure operational security.
* Maintain consistent formatting with existing UI tooltips.

---

## Revision History / Changelog

### 20260315_@1220 - Review: Hide Hook Driver Concept from Standard Admins

**Objective:**
Ensure that the technical mention of PHP Hook Drivers and the Adaptive Fallback mechanisms injected into the tooltips are completely hidden from standard WordPress Admins and are exclusively revealed to the authorized Superadmin.

**Actions Taken:**
1. Modified `assets/js/modules/generated-interface.js` to wrap the `wp-config` tooltip mutation logic in a check against `window.vaptSecureSettings?.isSuper` and `isSuperAdmin`.
2. Modified `assets/js/modules/aplus-generator.js` so that the preview tooltip snippet injection logic also strictly honors `window.vaptSecureSettings?.isSuper`.
3. Bumped plugin version `VAPTSECURE_VERSION` to 2.4.19.
4. Committed changes to version control using the message `"Hide Hook Driver technical details from non-superadmins"`.

**Status:** Completed. Standard users now see standard `wp-config.php` traces without mentions of the Adaptive Hook fallback, retaining operational secrecy from non-superadmin actors.

### 20260315_@1200 - Review: Hook Driver Fallback Implementation

**Objective:**
Investigate why the tooltip points to wp-config.php when the hook driver is the active enforcer, and update the tooltip text to reflect the actual Hook Driver fallback.

**Actions Taken:**
1. Ran the Browser Subagent to investigate the exact contents of the "Enable Protection" modal in the workbench. Verified the tooltip is titled "Technical Implementation Confirmation" and presents "wp-config" as the platform and code target payload.
2. Traced the UI logic to `assets/js/modules/generated-interface.js`.
3. Clarified the tooltip in `generated-interface.js` to modify `name` and `target` to include the `PHP Hook (Adaptive)` and `Hook Driver` fallback whenever the intended target is `wp-config`.
4. Appended the fallback snippet to visually demonstrate the hook implementation strategy (e.g. `add_action("init", "block_wp_cron", 1);`) in the "Technical Implementation Confirmation" section.
5. Made a parallel update for the same tooltips in the "Confirming Applied Protections" hover-over section.
6. Updated the `aplus-generator.js` so generated rules correctly map to the "Adaptive Fallback" syntax in the preview window for new A+ generated controls.
7. Bumped plugin version `VAPTSECURE_VERSION` to 2.4.17 in `vaptsecure.php`.
8. Committed changes to version control using the message `"Clarifying Hook Driver Fallback for wp-config targets in WorkBench UI"`.

**Status:** Completed. The Workbench UI now precisely clarifies to Superadmins exactly how `wp-config.php` operations fall back to the Hook Driver, satisfying the original concern while keeping the actual dual-enforcement design.
