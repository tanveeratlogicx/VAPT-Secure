# RISK_004_Remediation_Fix Implementation Plan

## Goal Description
The user noticed that for `http://hermasnet.local/wp-admin/admin.php?page=vaptsecure-workbench`, the `.htaccess` rules generate a placeholder: `# Protection logic should be provided via remediation field` instead of the actual full rule. The user also inquired about `Risk-004` (Email Flooding via Password Reset) and how its SKILL, Rule, or Workflow handles deployment.
Additionally, the user wants to update the "Save Status" button in the "Design Implementation" modal to dynamically change its label based on the implementation type.

Based on feedback, we need to address the following:
1.  **Placeholder String:** Remove the "ridiculous" placeholder logic completely and handle empty configurations gracefully.
2.  **Performance:** Implement a caching mechanism when loading `enforcer_pattern_library_v2.0.json` so it isn't loaded on the fly unnecessarily for every request.
3.  **File Location:** The AI previously suggested placing `vapt-functions.php` inside `wp-content/plugins/vapt-protection-suite/`. We will update the schema and AI context to save this strictly within the local `VAPT-Secure` plugin folder instead.
4.  **Code Removal on Deactivation:** Explain and ensure the mechanism that automatically removes these code blocks when a feature is disabled is properly active.
5.  **UI Improvement:** Ensure the UI tooltip next to "Enable Protection" explicitly displays the target file link and the code snippet for confirmation when using PHP Functions (or other enforcers).
6.  **Dynamic Modal Button:** Update the "Save Status" button in the "Design Implementation" modal to dynamically change its label to "Deploy" (if A+ Adaptive) or "Implement" (if Standard Script).

### Analysis of the Design Implementation Modal Button
- The modal is rendered by the `DesignModal` component in `assets/js/admin.js`.
- The state variable `isAdaptiveDeployment` tracks whether "A+ Adaptive Deployment" is enabled.
- Two buttons currently exist that trigger `handleSave`: 
    - A primary "Deploy" button (line 1316).
    - A secondary "Save Status" button (line 1318).
- We will update the secondary button's label (`Save Status`) to be dynamic: `isAdaptiveDeployment ? __('Deploy', 'vaptsecure') : __('Implement', 'vaptsecure')`.

### Analysis of the Placeholder Issue
1. The VAPT Secure workbench uses the REST API (`class-vaptsecure-rest.php`) to fetch feature definitions from `interface_schema_v2.0.json`.
2. `interface_schema_v2.0.json` uses a `code_ref` property pointing to `enforcer_pattern_library_v2.0.json`, rather than containing the raw rule code (`code`).
3. The frontend generator (`aplus-generator.js`) tries to build `.htaccess` rules. When it cannot find the code, it uses a fallback placeholder `# Protection logic should be provided via remediation field`. This occurs even for features like **Risk-004** that purely use PHP Functions and do not require `.htaccess` modifications.

### Analysis of vapt-functions.php and Rollback Workflow
For Risk-004 ("Email Flooding via Password Reset"):
- **Enforcer**: `PHP Functions`
- **Pattern**: `add_action_hook` on `login_init` to define `vapt_rate_limit_password_reset()`.
- **Target File Fix**: We need to update `vapt_driver_manifest_v2.0.json` to target `{VAPTSECURE_PATH}vapt-functions.php` (the local plugin directory) instead of the generic external folder.
- **Rollback Mechanism**: The orchestration engine (`class-vaptsecure-deployment-orchestrator.php`) intrinsically supports code removal based on the 'rollback' definition within `vapt_driver_manifest_v2.0.json`. When a feature is toggled OFF (or Transition to Develop happens), it reads the `begin_marker` and `end_marker` from the manifest and automatically purges that specific block from the target file.

## User Review Required
> [!IMPORTANT]
> **To the User:** 
> I am now updating the "Save Status" button label to be dynamic:
> - **A+ Adaptive Enabled**: Button says "Deploy"
> - **Standard Script (A+ Adaptive Disabled)**: Button says "Implement"
> This matches your request for dynamic labeling between "Deploy" and "Implement".

### Revision History

** (20260314_@1608) ** - Added requirement for dynamic Modal Button label ("Deploy" vs "Implement"). Cleaned up previous implementation plan versions.

** (20260314_@1535) ** - Backend resolution for `RISK-004` (Enforcer: `PHP Functions`, logic: `vapt_rate_limit_password_reset`) and `RISK-001` (`wp-config.php`) verified via `test_rest.php`. Caching implemented and functional. Moving to frontend tooltip implementation.

** (20260314_@1515) ** - Initial Plan created and approved.

---

## Proposed Changes

### Configuration Data
Update the driver manifest to point to the correct local target file.

#### [MODIFY] `data/vapt_driver_manifest_v2.0.json`
- Update `target_file_definitions` to map `"PHP Functions": "{VAPTSECURE_PATH}vapt-functions.php"`.
- Update the `"target_file"` parameter under `RISK-004`, `RISK-006`, `RISK-008`, and `RISK-009` (all PHP Functions) to reflect `{VAPTSECURE_PATH}vapt-functions.php`.

### WP REST API (Backend)
Modify the REST API to ensure `code` strings are correctly populated using a cached instance of the pattern library.

#### [MODIFY] `class-vaptsecure-rest.php`
- Add a static property `private static $cached_pattern_library = null;` to cache the pattern library during the request lifecycle.
- Create a helper method `get_cached_pattern_library()` that loads `enforcer_pattern_library_v2.0.json` only once per request and decodes it.
- Update the `get_features` method to utilize this cached library. When iterating over features and their `platform_implementations`, traverse the cached decoded JSON via the `code_ref` path to resolve the actual `code` and `wrapped_code`.
- Map the resolved `.htaccess` code to `$item['remediation']` for seamless backward compatibility.

### UI Generator (Frontend)
Ensure the UI handles empty implementations gracefully and displays the injected code snippets clearly to the user.

#### [MODIFY] `assets/js/modules/aplus-generator.js`
- **Fix Placeholder:** Update `suggestApacheRules` to return an empty string if no valid `.htaccess` code is resolved (e.g. for Risk-004).
- **Security Insights Tooltip:**
  - Update `vapt-description-summary-${riskId}` to iterate through `feature.platform_implementations`.
  - For each implementation, show:
    - **Target**: The `target_file` (e.g. `.htaccess` or `vapt-functions.php`).
    - **Code Preview**: A collapsible or direct view of the `code` field resolved from the backend.
  - This provides the "Confirmation" requested by the user.

#### [MODIFY] `assets/js/admin.js`
- **Dynamic Button Label:** Update the `DesignModal` component's "Save Status" button to use `isAdaptiveDeployment ? __('Deploy', 'vaptsecure') : __('Implement', 'vaptsecure')`.

## Verification Plan
### Automated Tests
- Run `curl` against the REST API `vaptsecure/v1/features` to verify that `platform_implementations` contains the loaded `code` field and that the code execution is fast (confirming caching).

### Manual Verification
- Go to `http://hermasnet.local/wp-admin/admin.php?page=vaptsecure-workbench`.
- Open Risk-003 and assure valid ModRewrite rules appear.
- Open Risk-004 and verify the ridiculous `.htaccess` placeholder is gone.
- Verify for Risk-004 that the Security Insights section explicitly mentions `VAPT-Secure/vapt-functions.php` and shows the `add_action('login_init', ...)` PHP snippet.
- Open the **Design Implementation** modal and toggle **A+ Adaptive Deployment**. Verify the bottom right button changes between **Deploy** and **Implement**.
