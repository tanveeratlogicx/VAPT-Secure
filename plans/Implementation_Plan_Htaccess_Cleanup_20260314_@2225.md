# Implementation Plan - .htaccess Cleanup & Tidying

**Task ID:** HTACCESS_CLEANUP  
**Timestamp:** 20260314_@2230 (GMT+5)

## 📋 Latest Comments / Suggestions

- **20260314_@2230:** Task completed. Redundant blank line removal implemented in both `VAPTSECURE_Htaccess_Driver` and `VAPTSECURE_Apache_Deployer`.
- **20260314_@2225:** Initial plan to implement redundant blank line removal in `.htaccess` drivers and deployers.

---

## 🎯 Goal

Ensure that the `.htaccess` file remains clean and tidy by collapsing multiple consecutive empty lines into a single empty line between rule blocks.

## 🛠️ Proposed Changes

### 1. `VAPTSECURE_Htaccess_Driver` (`includes/enforcers/class-vaptsecure-htaccess-driver.php`)

- **Status:** COMPLETED
- Implement a `tidy_content($content)` private method (implemented inline in `write_batch`) that uses regex to collapse `\n{3,}` into `\n\n`.
- Call this method in `write_batch()` before writing the content to disk.

### 2. `VAPTSECURE_Apache_Deployer` (`includes/enforcers/class-vaptsecure-apache-deployer.php`)

- **Status:** COMPLETED
- Implement a similar tidy logic.
- Call it in `write_rules()` and `ensure_global_whitelist()` before saving.

## 🧪 Verification Plan

- **Manual Check:** Inspect the `.htaccess` file after a deployment to ensure no multiple blank lines exist.
- **Automated Check:** Trigger a rule update and verify the file content programmatically (via grep or reading file).

## 📅 Revision History

- **20260314_@2230:** Mark task as completed.
- **20260314_@2225:** Created implementation plan.
