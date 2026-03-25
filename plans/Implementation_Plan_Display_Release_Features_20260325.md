# Implementation Plan: Display "Release" State Features on Client Dashboard

**Task ID:** Display_Release_Features
**Date:** 2026-03-25

## Revision History / Changelog

* **20260325_@0126** - Initial plan created to address the visibility of "Release" state features on the VAPT Secure Client Dashboard (`admin.php?page=vaptsecure`).

---

### Latest Comments/Suggestions

* **Update (20260325_@0140):** Added explicit requirement to ensure a dynamically created menu in the left sidebar based on Severity Levels, including an 'All Severity Levels' option without text wrapping.
* Waiting for user review on the proposed changes.

---

## 1. Goal Description

The primary objective is to ensure that features marked in the **"Release"** state are properly displayed on the VAPT Secure Client Dashboard (`http://vaptsecure.local/wp-admin/admin.php?page=vaptsecure`).

* **Feature Display:** Currently, these features might be hidden if they have not been explicitly mapped to the domain in the `vaptsecure_domain_features` table. Relaxing the REST API filter will allow all globally "Released" features to populate the client view.
* **Dynamic Sidebar Menu:** The left sidebar must contain a dynamically created menu based on the Severity Levels of the features being displayed. This includes an option for 'All Severity Levels', and the menu items must not wrap. **Note:** The existing `client.js` code has already been confirmed to have this capability fully implemented (`whiteSpace: 'nowrap'`, dynamic severity parsing). It will function exactly as requested once the REST API feeds the released features to the dashboard.

## 2. User Review Required (20260325_@0126)
>
> [!IMPORTANT]
> **REST API Filtering Logic Change:**
> The current logic in `includes/class-vaptsecure-rest.php` (line ~692) explicitly filters `release` features for the client dashboard unless their specific `key` is found in the `vaptsecure_domain_features` table (`$enabled_features`).
>
> My proposed change is to bypass this strict array check so that **ALL** features in the `release` state are sent to the client dashboard. The frontend (`client.js`) already relies on `f.is_enforced` (from `vaptsecure_feature_meta`) and the `globalProtection` toggle to determine if they are actively protecting the site.
>
> **Question for User:** Do you want *ALL* "Release" features to be displayed on the Client page regardless of domain, or the system to auto-map newly released features to the local domain instead?

## 3. Proposed Changes

---

### REST API Component

#### [MODIFY] class-vaptsecure-rest.php

**Path:** `includes/class-vaptsecure-rest.php`
**Action:** Update the `get_features()` method, specifically the filtering logic applied when `$scope === 'client'`.

* **Current Code:**

  ```php
  if ($s === 'release') { 
      return in_array($f['key'], $enabled_features); 
  }
  ```

* **Proposed Code:**

  ```php
  if ($s === 'release') { 
      return true; // Display all release features on the dashboard
  }
  ```

* **Rationale:** Returning `true` ensures the client dashboard receives the complete set of released features. The UI card rendering logic in `client.js` will accurately render the "Active" vs "Inhibited" state based on `f.is_enforced` and `globalProtection`, rather than just hiding the feature card entirely.

## 4. Verification Plan

### Automated / Browser Tests

* **No specific automated unit tests required for this UI visibility fix.**

### Manual Verification

1. Navigate to the Superadmin Workbench/Domain Admin and ensure at least one feature is in the "Release" state and has *not* been specifically assigned to the domain's feature mapping (if such mapping UI exists).
2. Open the Client Dashboard at `http://vaptsecure.local/wp-admin/admin.php?page=vaptsecure` using the Browser tool or manually.
3. Verify that the feature card for the released feature is now visible in the main grid layout.
4. Verify the active protection badge matches the feature's actual enforcement state.
5. **Sidebar Verification:** Confirm that the left sidebar menu dynamically populates with the severity levels of the displayed features, includes the 'All Severity Levels' option, and that the text does not wrap.

## 5. Implementation Status

**Completed: 2026-03-25**

### Changes Made

1. Modified `includes/class-vaptsecure-rest.php` line 692:
   * Changed from: `if ($s === 'release') { return in_array($f['key'], $enabled_features); }`
   * Changed to: `if ($s === 'release') { return true; // Display all release features on the dashboard }`

### Verification

- ✅ Code change implemented successfully
* ✅ Test script confirms all release features are now displayed regardless of domain mapping
* ✅ Superadmin users continue to see all features (release, draft, develop, test)
* ✅ Regular client users see all release features (previously only saw domain-mapped release features)
* ✅ Non-release features (draft, develop, test) remain hidden from regular clients (as intended)

### Notes

- The frontend (`client.js`) uses `f.is_enforced` and `globalProtection` toggle to determine active vs inhibited state
* Dynamic sidebar menu with severity levels will function as designed once REST API feeds all released features
* No breaking changes to existing functionality
