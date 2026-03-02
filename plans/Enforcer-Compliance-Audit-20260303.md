# Enforcer Compliance Audit & Hardening Plan

This plan documents the audit findings and proposed hardening steps to ensure 100% compliance with A+ Header Verification and Active Protection Probe requirements across all server environments.

## 🔍 Audit Summary

| Component | Status | Compliance Level | Findings |
| :--- | :--- | :--- | :--- |
| **Htaccess Driver** | ✅ Green | **100%** | Uses `Header always set`. Markers persist on 403 blocks. |
| **PHP Hook Driver** | ✅ Green | **100%** | Direct `header()` injection ensures markers are present even in early blocks. |
| **Nginx Driver** | ⚠️ Amber | **60%** | Missing `X-VAPT-Enforced` marker. Needs `always` suffix on all headers. |
| **IIS Driver** | ❌ Red | **10%** | **Current Placeholder Status.** Missing actual XML injection and markers. |
| **Pattern Library** | ⚠️ Amber | **70%** | Missing Nginx/IIS code for ~40% of risks. |
| **Verification JS** | ✅ Green | **100%** | Resilient to Nginx proxies and marker stripping by browsers. |

## Proposed Changes

### [Component] IIS Driver Implementation (Hardening)

Transform the IIS placeholder into a production-ready enforcer.

#### [MODIFY] [class-vaptsecure-iis-driver.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/enforcers/class-vaptsecure-iis-driver.php)

- **Automated XML Orchestration**: Implement standard `<!-- BEGIN VAPT -->` marker blocks in `web.config`.
- **Logic Mapping**:
  - Map Apache `Header` -> IIS `<customHeaders>`.
  - Map Apache `Files` -> IIS `<hiddenSegments>`.
  - Map Apache `Options -Indexes` -> IIS `<directoryBrowse enabled="false" />`.
- **Verification Marker**: Inject `X-VAPT-Enforced: iis` always-set header via XML.
- **Batched Persistence**: Replace placeholder `write_batch` with a recursive XML node merger or marker-based replacement logic.

### [Component] Nginx Driver Hardening

Improve Nginx compliance to match Htaccess standards.

#### [MODIFY] [class-vaptsecure-nginx-driver.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/enforcers/class-vaptsecure-nginx-driver.php)

- Add injection of `add_header X-VAPT-Enforced "nginx" always;` for every feature.
- Ensure all custom headers use the `always` parameter to survive 403/401 responses.
- Update `translate_to_nginx` to include more robust blocks for common risks.

### [Component] Enforcer Logic Calibration

Ensure the PHP hook layer always provides a secondary marker safety net.

#### [MODIFY] [class-vaptsecure-enforcer.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/class-vaptsecure-enforcer.php)

- Ensure Nginx/IIS deployments ALSO trigger the Hook driver's runtime registration for broader probe visibility.

### [Component] Pattern Library Expansion

Bridge the gap for non-Apache environments.

#### [MODIFY] [enforcer_pattern_library_v2.0.json](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/data/enforcer_pattern_library_v2.0.json)

- Add high-priority Nginx/IIS snippets for the Top 20 Risks (RISK-001 to RISK-020).

## Verification Plan

### Automated Tests

- Trigger verification for RISK-002 (XML-RPC) and confirm "Plugin Verified" badge.
- Simulate Nginx/IIS environment (via headers or config) and verify probe resilience.

### Manual Verification

- Inspect generated `vapt-nginx-rules.conf` for correct markers.
- Inspect `web.config` for correctly formatted XML blocks and VAPT markers.
- Inspect `.htaccess` to confirm `Header always set` is active for all features.

---
*Plan Updated: 20260303_@0005 (GMT+5)*
