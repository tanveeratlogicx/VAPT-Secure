# Housekeeping and Feature Readiness Review

- **20260314_@2325** 🔴 `[Need Review]` - Proposed housekeeping to enforce root cleanliness and confirm readiness for feature implementation.

## Latest Comments/Suggestions
- 🧹 **Root Cleanliness**: Relocating `vapt-debug.txt` to the `/plans/` directory to adhere to the project's cleanliness standards (Rule 2 in VAPT-IPS).
- 🧠 **Context Sync**: Confirmation that all local resources (Interface Schema v2.0, Enforcer Pattern Library v2.0, Agent Instructions v2.0) are prioritized as the primary source of truth.
- 🚀 **Next Steps**: Move into implementation of pending features as defined in the risk catalogue.

## Proposed Changes

### [Housekeeping / Root Cleanliness]

#### [MODIFY] [class-vaptsecure-hook-driver.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/enforcers/class-vaptsecure-hook-driver.php)
- Update `$log_file` path to point to `VAPTSECURE_PATH . 'plans/vapt-debug.txt'`.

#### [MODIFY] [class-vaptsecure-build.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/class-vaptsecure-build.php)
- Update exclusion list for `vapt-debug.txt` to handle its new location if necessary, or ensure it's still excluded from builds.

#### [DELETE] [vapt-debug.txt](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/vapt-debug.txt)
- Relocate the file to `plans/vapt-debug.txt`.

## Verification Plan

### Manual Verification
1. **Log Path Verification**: Trigger a security enforcement event (e.g., attempt to access a protected REST endpoint) and verify that logs are written to `plans/vapt-debug.txt` instead of the root.
2. **Root Cleanliness Check**: confirm `vapt-debug.txt` no longer exists in the plugin root.
3. **Schema Integrity**: Verify that I can successfully query a risk ID (e.g., RISK-003) from the local `interface_schema_v2.0.json` and retrieve the correct enforcer.
