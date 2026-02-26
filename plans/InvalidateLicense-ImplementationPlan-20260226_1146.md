# Invalidate License Feature Plan

> Latest Comment: We need the ability to "Invalidate" a license if needed. (20260226_@1145)

This plan outlines the steps to add an "Invalidate License" feature to the VAPT Secure License Management tab.

## Proposed Changes

### 1. Backend REST API

#### [MODIFY] `class-vaptsecure-rest.php`

- Modify the `update_domain` callback handling the `/v1/domains/update` endpoint.
- Add an `if ($action === 'invalidate')` logic block:
  - Set `$is_enabled = 0`.
  - Set `$auto_renew = 0`.
  - Update the history JSON with an invalidation revocation record.
  - Set `$manual_expiry_date = null` (or yesterday's date) so the license registers as immediately expired.

### 2. Frontend React Component

#### [MODIFY] `admin.js`

- **Invalidate Button:** Add an "Invalidate License" button within the `.vapt-correction-controls` or directly next to the "Manual Renew" button, utilizing the `isDestructive: true` property. This button will only display if the domain is currently enabled (`is_enabled != false`).
- **Confirmation Modal:** Update the `VAPTSECURE_ConfirmModal` implementation to support a new `confirmState.type === 'invalidate'` state.
  - Message: `"Are you sure you want to completely invalidate and revoke this license?"`
  - The modal will trigger `executeRollback` which relies on the backend `$action = 'invalidate'`.
- **Validation State UI:** Ensure that domains marked as `is_enabled === 0` visually show as "Invalidated" (e.g., strike-through, red badges, grayed-out controls). (Note: The usage visualizer already checks `is_enabled` and ignores them).

### 3. Version Bump

#### [MODIFY] `vaptsecure.php`

- Bump the version to force clear the browser's aggressive caching.

## User Review Required

None - straightforward REST API functionality bridging.

## Verification Plan

1. **Manual Verification**: Assign a domain a valid Pro license. Click the "Invalidate License" button. Confirm the domain's limit is instantly revoked and `is_enabled` drops to 0. Verify the progress bar updates locally.
