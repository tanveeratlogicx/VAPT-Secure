# License Management Enhancements & Domain Glitch Fix

## Goal Description

The user has identified a UI/UX issue on the "License Management" tab where adding a domain is confusing and prone to error (currently, editing the Domain Name field while selecting an existing domain renames it rather than creating a new one). Additionally, there is a bug where adding a new domain from the "Domains Feature" tab fails to automatically attach a `License ID` to the newly created domain.

This plan details the UI enhancements for the License tab to cleanly separate "Update License" and "Add Domain" flows, and a backend fix to ensure License IDs are properly initialized.

## Proposed Changes

### 1. `assets/js/admin.js` (Frontend UI)

#### [MODIFY] `t:\~\Local925 Sites\hermasnet\app\public\wp-content\plugins\VAPT-Secure\assets\js\admin.js`

- **LicenseManager Component Enhancements**:
  - **Add a "Create New Domain" Mode**: Introduce a UI state (`isCreatingNew`) that toggle the form from "Edit Mode" to "Create Mode".
  - **UI Visuals**:
    - Add a `+ Add New Domain` button next to the "Select Domain Context" dropdown.
    - When "Create New Domain" is active, clear the form fields (`domain`, `license_id`, etc.) and change the submit button to read **"Register Domain & License"**.
    - When "Edit Mode" is active, change the "DOMAIN NAME" field to be **read-only** (or clearly marked as Rename) to prevent accidentally renaming a domain when the user actually wanted to add a new one.
  - **Submit Logic**: Ensure `handleUpdate` passes no `id` when in create mode, so the backend interprets it as an insertion rather than an update.

- **DomainFeatures Component Fix**:
  - Update the `addDomain` function call to default the `license_id` generation or rely on the backend to do so, ensuring the frontend reflects the newly attached ID.

### 2. `includes/class-vaptsecure-rest.php` (Backend API)

#### [MODIFY] `t:\~\Local925 Sites\hermasnet\app\public\wp-content\plugins\VAPT-Secure\includes\class-vaptsecure-rest.php`

- **`update_domain` Endpoint Fix**:
  - When creating a *new* domain (where `$current` is null), if `$license_id` is empty or null, **auto-generate a default License ID**.
  - Example logic: `if (!$current && empty($license_id)) { $license_id = 'DEV-' . strtoupper(substr(md5(uniqid()), 0, 9)); $license_type = 'developer'; }`
  - This ensures that domains added from the simplistic "Domain Features" tab will immediately receive a valid License ID and avoid the reported glitch.

## Verification Plan

### Automated / Backend Tests

- Create a new domain without providing a License ID. Verify the database auto-populates `license_id`.
- Check REST API responses for domain creation.

### Manual Verification

1. Open the VAPT-Secure Superadmin Dashboard.
2. Navigate to the **License Management** tab.
3. Click **"+ Add New Domain"**. Verify the form resets and the CTA button changes.
4. Enter a new domain and random license details. Click **Register Domain & License**. Verify it appears in the dropdown and list.
5. Select an existing domain in the dropdown. Verify the Domain Name field cannot be accidentally modified to screw up the existing record (or clearly indicates rename).
6. Navigate to the **Domain License Directory / Features** tab. Add a quick domain.
7. Verify that the quick-added domain now has a generated `License ID` in the table instead of being blank.
