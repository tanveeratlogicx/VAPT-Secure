# Implementation Plan - License Management Enhancements

Improve the visual aesthetics of the License Management Tab and implement functionality to manage license allocations (License ID and Installation Limits) across multiple domains.

## Latest Comments/Suggestions (2026-02-26)

- Enhance visual appeal with premium design elements (gradients, glassmorphism, micro-animations).
- Implement central management for License IDs across multiple domains.
- Display "Installation Usage" (Current/Total) for each License Key.
- Add `license_id` field to the domain management form.

---

## User Review Required
>
> [!IMPORTANT]
> The License ID field will now be directly editable in the License Management form. This allows manual assignment of License Keys to domains.
> A new "License Allocation Overview" will be added to summarize usage across all domains.

## Proposed Changes

### Core Plugin

#### [MODIFY] [vaptsecure.php](file:///t:/~/Local925%20Sites/hermasnet%20app/public/wp-content/plugins/VAPT-Secure/vaptsecure.php)

- Bump version from `2.1.2` to `2.2.0`.

### Admin Interface (React/Gutenberg) 20260226_@1023

#### [MODIFY] [admin.js](file:///t:/~/Local925%20Sites/hermasnet%20app/public/wp-content/plugins/VAPT-Secure/assets/js/admin.js)

- **`LicenseManager` Component Update**:
  - Add `license_id` field to the "Update Form" to allow assigning/changing license keys.
  - Implement a `licenseUsage` calculation to count domains per `license_id`.
  - Add a "Usage Progress Bar" in the domain directory table.
  - Refactor the table to use a more premium, card-like row structure if appropriate, or enhance the existing table with better aesthetics.
  - Update `sortedDomains` useMemo to include license usage metadata.

### Styling

#### [MODIFY] [admin.css](file:///t:/~/Local925%20Sites/hermasnet%20app/public/wp-content/plugins/VAPT-Secure/assets/css/admin.css)

- Implement a premium design system for the license tab:
  - `.vapt-license-card`: Add subtle gradients and `backdrop-filter: blur()`.
  - `.vapt-license-badge`: Use dynamic HSL colors for better visual harmony.
  - Add `.vapt-usage-bar`: A progress bar to visualize installation limits.
  - Improve typography and spacing for high-density information.
  - Add micro-animations (scale/shadow) on hover for cards and buttons.

### Documentation & History

#### [NEW] [LicenseManagement-ImplementationPlan-20260226.md](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/plans/LicenseManagement-ImplementationPlan-20260226.md)

- Per User Global Rule: Persist a copy of this plan in the local plugin folder.

---

## Verification Plan

### Manual Verification

- **Visual Audit**: Navigate to `vaptsecure-domain-admin` and verify the "License Management" tab UI looks premium and follows the new design language.
- **License Assignment**: Edit a domain, enter a new `license_id`, and save. Verify the ID is persisted in the database.
- **Allocation Check**: Assign the same `license_id` to two different domains. Check the domain directory for an accurate count (e.g., "2 of 5" installations used).
- **Limit Enforcement**: verify the progress bar turns red if the current count exceeds the `installation_limit`.
- **Responsive Check**: Ensure the two-column grid collapses gracefully on smaller screens.
