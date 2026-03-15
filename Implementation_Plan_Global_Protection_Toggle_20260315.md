# Implementation Plan - Global Protection Toggle Enhancements (20260315)

## 📋 Task: Implement Master Global Protection Toggle
**Status**: PLANNING
**Date**: 2026-03-15

---

### Latest Comments/Suggestions
*   **UI UPDATE**: Need to ensure feature cards clearly show an "Inhibited" state when the master switch is OFF.
*   **PROBE TESTING**: Probes will run actual tests even when disabled, reporting real-time "Vulnerable" status to accurately reflect the site's state.

---

### [Component] Backend: Protection Restoration Logic
- [ ] Verify `VAPTSECURE_Enforcer::rebuild_all()` encompasses all active drivers.
- [ ] Ensure transient cache `vaptsecure_active_enforcements` is purged on every toggle.

### [Component] Frontend: UI Feedback Enhancements
- [ ] **client.js**: 
    - Update master toggle card status label when disabled.
    - Change card aesthetics (border/shadow) for "Inhibited" state.
- [ ] **generated-interface.js**:
    - Add "Inhibited (Master Switch OFF)" pill/badge to feature cards.
    - Ensure `TestRunnerControl` allows real-time execution even when inhibited.
    - Add warning message above "Run Verify" button explaining the "Inhibited" context.
    - **Grey out and disable** individual "Enable Protection" toggles when global protection is OFF.

---

### Verification Plan

#### 🤖 Automated/Browser Tests
- **Test Case 1: Global Kill Switch**
    - Disable master toggle.
    - Check `.htaccess` for absence of VAPT rules.
    - Run "Header Verification" and observe failure + UI warning.
- **Test Case 2: Global Restore**
    - Enable master toggle.
    - Check `.htaccess` for presence of VAPT rules.
    - Run "Header Verification" and observe success.

#### 👷 Manual Verification
- Visual check of "Inhibited" state on feature cards.
- Confirm no-wrap styling is preserved in all sidebars/menus.

---

## Revision History
*   **20260315_@0950**: Initial plan created for Global Protection Toggle implementation.
