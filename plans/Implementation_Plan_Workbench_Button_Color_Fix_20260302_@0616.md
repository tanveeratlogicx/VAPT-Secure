# Bug Fix: A+ Workbench Button Color Not Updating (20260302_@0616)

The "A+ Workbench" button color stays blue instead of turning green after clicking "Implement" in the Design Implementation Modal. This is because the `is_enforced` flag is calculated solely from the `feat_enabled` key in `localImplData`. Since `localImplData` is empty by default, the feature is saved with `is_enforced = 0`, even though the UI shows it as enabled (via schema defaults).

## Latest Updates (20260302_@0616)

- Identified the root cause in `admin.js`.
- Proposed a fix that considers schema defaults for `is_enforced`.
- Planned a version bump to `2.3.6`.

## Proposed Changes

### [VAPT-Secure](t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure)

#### [MODIFY] [admin.js](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/js/admin.js)

Refine the `is_enforced` calculation in `handleSave` to check both `localImplData` and the schema defaults.

```javascript
// Around line 443
is_enforced: (() => {
  // 1. Check explicit user interaction first
  if (localImplData && localImplData.feat_enabled !== undefined) {
    const val = localImplData.feat_enabled;
    return (val === true || val === 1 || val === '1' || val === 'true') ? 1 : 0;
  }
  // 2. Fallback to schema default if no interaction has occurred
  const featEnabledControl = (parsed.controls || []).find(c => c.key === 'feat_enabled');
  if (featEnabledControl && featEnabledControl.default !== undefined) {
    const def = featEnabledControl.default;
    return (def === true || def === 1 || def === '1' || def === 'true') ? 1 : 0;
  }
  return 0;
})(),
```

#### [MODIFY] [vaptsecure.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/vaptsecure.php)

Bump version to `2.3.6`.

## Verification Plan

### Manual Verification

1. Open the VAPT Secure Dashboard.
2. Select a feature in "Develop" status that hasn't been implemented yet (A+ Workbench button is Blue).
3. Click "A+ Workbench" to open the Design Implementation Modal.
4. Verify that "Enable Protection" toggle is shown as ON (Green).
5. Click the **Implement** button without touching the toggle.
6. Verify that the "A+ Workbench" button on the dashboard turns **Green** as expected.
7. Open the modal again, toggle it OFF, click **Implement**, and verify it turns **Orange/Blue** (depending on status).

### Automated Tests

*None available in the current codebase.*
