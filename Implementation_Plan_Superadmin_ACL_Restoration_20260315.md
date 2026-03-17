# Implementation Plan - Restricting Administrative Access and Hiding URLs

This plan outlines the steps to restrict access to sensitive VAPT Secure administrative pages (`vaptsecure-domain-admin` and `vaptsecure-workbench`) so they are only accessible to the authorized Superadmin, even on localhost environments.

## Latest Comments/Suggestions (20260315_@0826)

* **UI UPDATE (v2.4.20)**: Renamed "All Features" to "All Severity Levels".
* **Dynamic Menu**: Sidebar now dynamically shows/hides "Medium Severity" and "Low Severity" based on actual feature counts.
* **Styling**: Enforced `nowrap` on menu items for premium sidebar aesthetics.
* **Cache Management**: Version bumped to `2.4.20` with dynamic cache-busting during implementation.
* **SUCCESS**: Tiered ACL and Dashboard visibility successfully restored.
* **Top-Level Menu**: Visible to all WordPress Admins (e.g., `cosmictechsol`).
* **Dashboard Content**: Admins now see features in the "Release" state that have been enabled for the current domain (`hermasnet.local`).
* **Sensitive Submenus**: (`Workbench`, `Domain Admin`) strictly hidden from non-superadmins and blocked at routing layer.
* **Superadmin Identity**: Maintains full access to all areas via identity match.
* **Legacy Session mitigation**: Only the hardcoded Superadmin identity can bypass the primary firewall.

## Task Overview

* **Status**: Completed (20260315_@0805)
* **Complexity**: 4/10
* **Date**: 20260315_@0805

## 1. Audit and Refine Superadmin Identity Check (Completed 20260315_@0805)

* **File**: `vaptsecure.php`
* **Action**: Verified `is_vaptsecure_superadmin()` strictly handles identity and OTP status.
* **Verification**: Regular admins no longer meet the criteria for superadmin by default.

## 2. Hide ALL Menus from Non-Superadmins (20260315_@0817)

* **File**: `vaptsecure.php`
* **Action**: Update `vaptsecure_add_admin_menu` to use `is_vaptsecure_superadmin(false)` (Identity check only) for registering ALL related pages.
* **Effect**: Regular admins will no longer see even the top-level "VAPT Secure" menu.

## 3. Harden Render Functions with Strict Identity + Auth (20260315_@0818)

* **File**: `vaptsecure.php`
* **Action**: Update `is_vaptsecure_superadmin()` to accept a `$force_auth` parameter and ALWAYS verify identity first.
* **Action**: Use `is_vaptsecure_superadmin(true)` in render functions to force BOTH identity and OTP session.
* **Effect**: Unauthorized users (even with legacy sessions) are immediately ejected.

## 4. Final Verification (Completed 20260315_@0832)

### Testing Results (Tiered Access & Content)

* **Admin User (`cosmictechsol`)**:
  * Sidebar: "VAPT Secure" main menu is **Visible**.
  * Submenus: "Workbench" and "Domain Admin" are **Hidden**.
  * Access: `admin.php?page=vaptsecure` leads to the Dashboard.
  * **Feature Visibility**: RISK-009, 010, 011, and 012 are **Visible** (as they are in "Release" state and enabled for domain).
  * Access: Direct URLs to submenus are **Blocked** (OK).
* **Superadmin Identity (`tanmalik786`)**:
  * Sidebar: All menus and submenus are **Visible**.
  * Access: Full access to all dashboards (OK).

---
Created by Antigravity AI
