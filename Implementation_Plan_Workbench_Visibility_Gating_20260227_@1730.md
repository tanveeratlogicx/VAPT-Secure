# Implementation Plan: Workbench Visibility Gating (is_pushed flag)

## Goal Description

Correct the "Premature Workbench Push" issue. Currently, transitioning a feature to "Develop" (Step 1) makes it immediately visible in the Implementation Dashboard (Workbench). The goal is to ensure features only appear in the Workbench after being explicitly "Deployed" (Step 2) from the Design Implementation modal.

## Revision History

### 20260227_@1730 - Implementation & Final Verification

- [x] Successfully implemented `is_pushed` flag across DB, REST, and Frontend.
- [x] Fixed REST API `update_feature` whitelist to include `is_pushed`.
- [x] Verified end-to-end: 'Develop' status keeps features hidden; 'Deploy' reveals them.
- [x] Verified identity matching AJAX fix in `admin.js`.

---

## Proposed Changes

### [MODIFY] [vaptsecure.php]

- **Database Schema**: Added `is_pushed` TINYINT(1) DEFAULT 0 to the `vaptsecure_feature_meta` table.
- **Migration**: Flagged existing `Release` features as `is_pushed = 1`.

### [MODIFY] [class-vaptsecure-db.php]

- **Schema Map**: Added `is_pushed => '%d'` to `$schema_map` in `update_feature_meta`.

### [MODIFY] [admin.js]

- **Step 1 (Develop)**: Updated `confirmTransition` to set `is_pushed: 0` and `is_enforced: 0`.
- **Step 2 (Deploy)**: Updated `handleSave` in `DesignModal` to set `is_pushed: 1` and `is_enforced` based on the protection toggle.
- **AJAX Fix**: Fixed identity matching in `updateFeature` for dynamic UI updates.

### [MODIFY] [class-vaptsecure-rest.php]

- **REST Whitelist**: Added `is_pushed` to captured params in `update_feature`.
- **REST Filtering**: Updated `get_features` (scope=client) to hide `develop`/`test` features unless `is_pushed` is true.

### [MODIFY] [workbench.js] / [client.js]

- **UI Filtering**: Updated to explicitly check for the `is_pushed` flag.

## Verification Plan

### Manual Verification Completed

1. Transitioned RISK-083 to **Develop**. Verified it was **NOT** in the Workbench.
2. Clicked **A+ Workbench**, then **Deploy**. Verified it **APPEARED** in the Workbench.
3. Verified button color turns **Blue** on Develop and **Green** on Enforced.
