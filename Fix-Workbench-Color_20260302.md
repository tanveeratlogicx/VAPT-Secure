# Fix: A+ Workbench Button Color Update on Deploy

**Task ID:** Fix-Workbench-Color  
**Version Target:** 2.3.7  
**Plugin:** VAPT-Secure  
**Status:** ✅ IMPLEMENTED (20260302_@1337)

---

## Revision History / Changelog

### [v1] 20260302_@1334 — Initial Plan

#### Problem Background

After clicking **Deploy** (or **Implement**) in the `DesignModal`, the "**A+ Workbench**" button in the feature table does **not** change color to green. It remains blue/orange depending on lifecycle stage.

#### Root Cause

The button color logic (in `admin.js` ~line 4924) checks `f.is_enforced == 1`:

```js
background: (f.status === 'Develop') && (f.is_enforced == 1) ? '#10b981' : '#3b82f6'
```

The `handleSave` IIFE computes `is_enforced = 0` when:

- `localImplData.feat_enabled` is undefined (user hasn't interacted with the toggle in the preview), AND
- The A+ adaptive schema either has no `feat_enabled` control or defaults it to `false`.

Since `updateFeature` does an optimistic update, `is_enforced: 0` is written back to the React features state, and the button stays blue.

#### Fix

In the `handleSave` `is_enforced` IIFE, add a short-circuit at the top:

```js
// NEW: Deploying via A+ Adaptive always implies enforcement
if (isAdaptiveDeployment) return 1;
```

This ensures `is_enforced: 1` is sent whenever the user clicks **Deploy** (A+ adaptive mode), so the button turns green immediately via the optimistic update.

#### Version Bump

- `vaptsecure.php`: `2.3.6` → `2.3.7`

---

### Files Changed

| File | Change |
|------|--------|
| `assets/js/admin.js` | Add `if (isAdaptiveDeployment) return 1;` in `handleSave` IIFE |
| `vaptsecure.php` | Version bump `2.3.6` → `2.3.7` |
