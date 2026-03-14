# RISK_004_Remediation_Fix Implementation Plan

## 📑 Table of Contents (Status-Based Priority)
- 🟢 [20260314_@1628 [Complete]](#20260314_@1628-complete) - Version Bump to 2.4.14 & Final Release
- 🟢 [20260314_@1623 [Complete]](#20260314_@1623-complete) - Implemented Dynamic Modal Button label
- 🟢 [20260314_@1554 [Complete]](#20260314_@1554-complete) - Approved Dynamic Modal Button requirement
- 🟢 [20260314_@1614 [Complete]](#20260314_@1614-complete) - Strict Plugin Root Cleanup Rule
- 🟢 [20260314_@1604 [Complete]](#20260314_@1604-complete) - Dedicated folder enforcement & Dynamic Button implementation
- 🟢 [20260314_@1601 [Complete]](#20260314_@1601-complete) - Establishing Universal IPS Rule & Dynamic Button Label
- 🟢 [20260314_@1535 [Complete]](#20260314_@1535-complete) - Backend resolution verified (RISK-004)
- 🟢 [20260314_@1515 [Complete]](#20260314_@1515-complete) - Initial Plan created

---

## 🟢 20260314_@1628 [Complete]
- **Goal**: Bump version to next patch release.
- **Outcome**: Version updated to **2.4.14** in `vaptsecure.php`. Final verification of root cleanup is active.

## 🟢 20260314_@1623 [Complete]
- **Goal**: Implement dynamic button text logic.
- **Outcome**: Successfully modified `assets/js/admin.js`. The button text now adapts to the deployment mode (A+ Adaptive → Deploy, Standard → Implement).

## 🟢 20260314_@1554 [Complete]
- **Goal**: Integrated user request for dynamic button labels in `DesignModal`.
- **Outcome**: Technical detail approved. Proceeding with implementation.

## 🟢 20260314_@1614 [Complete]
- **Goal**: Implement strict plugin root cleanup.
- **Outcome**: Successfully relocated dozens of internal files. The root directory now only contains `README.md`, `vaptsecure.php`, and `LICENSE`. Standards enforced in `.windsurfrules`.

## 🟢 20260314_@1604 [Complete]
- **Goal**: Relocated all documentation to `plans/` folder.
- **Status**: Universal Rule updated in `.windsurfrules`. Proceeding to `admin.js` for dynamic button label.

## 🟢 20260314_@1601 [Complete]
- **Goal**: Established Universal Implementation Plan Rule (VAPT-IPS) in `.windsurfrules`.
- **Status**: Structuring current plan to match the new rule. Preparing to implement the dynamic button label.

## 🟢 20260314_@1535 [Complete]
- **Goal**: Verify backend resolution for `RISK-004`.
- **Outcome**: Verified via `test_rest.php`. `PHP Functions` enforcer and `vapt-functions.php` pathing confirmed.

## 🟢 20260314_@1515 [Complete]
- **Goal**: Initial remediation fix plan.
- **Outcome**: Plan approved for placeholder removal, caching, and tooltip enhancements.

---

## 🎯 Persistent Goal Description
1.  **Placeholder String:** Remove the "ridiculous" placeholder logic completely.
2.  **Performance:** Implement a caching mechanism for `enforcer_pattern_library_v2.0.json`.
3.  **File Location:** Target `{VAPTSECURE_PATH}vapt-functions.php` locally.
4.  **Code Removal:** Ensure rollback markers are respected for automatic cleanup.
5.  **UI Tooltips:** Display target file and code snippet in workbench tooltips.
6.  **Dynamic Modal Button:** Update the "Save Status" button in `DesignModal` to show "Deploy" (A+ Adaptive) or "Implement" (Standard).
7.  **Dedicated Folder:** Store all plans and documentation strictly in the `/plans/` directory to avoid root clutter.
8.  **Strict Root Cleanliness:** The plugin root folder MUST only contain `README.md` and `User Guide` (at most). All other files (MD fixes, patches, reports, zips, scripts) must be relocated.

## 🛠️ Proposed Changes

### Configuration Data
#### [MODIFY] `data/vapt_driver_manifest_v2.0.json`
- Map `"PHP Functions": "{VAPTSECURE_PATH}vapt-functions.php"`.

### WP REST API (Backend)
#### [MODIFY] `includes/class-vaptsecure-rest.php`
- Caching implementation and `code_ref` resolution.

### UI Generator (Frontend)
#### [MODIFY] `assets/js/modules/aplus-generator.js`
- Remove placeholder logic.

#### [MODIFY] `assets/js/admin.js`
- **Dynamic Button Label:** Update `DesignModal` button (line 1318) to use `isAdaptiveDeployment ? __('Deploy', 'vaptsecure') : __('Implement', 'vaptsecure')`.

## ✅ Verification Plan
- **Automated**: `curl` REST API for logic verification.
- **Manual**: Verify tooltips and dynamic button text in the VAPT Workbench.
