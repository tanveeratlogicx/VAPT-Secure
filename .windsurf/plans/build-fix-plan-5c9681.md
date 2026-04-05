# Build Generation and Version Mismatch Fix Plan

This plan addresses the issues where the generated build's configuration file is empty when "Restrict Features" is disabled, the version mismatch between the plugin and frontend, and the "No released protections found" error in the generated build.

## Proposed Changes

### 1. Build Generation Logic (`includes/class-vaptsecure-build.php`)
- **Fix Empty Config**: Modify `generate_config_content` to include all implemented feature IDs when `restrict_features` is false. This ensures the configuration file is not empty and the plugin knows which features are available.
- **Ensure Feature Keys**: Verify that the `features` array passed to the generator contains the correct internal keys (e.g., `RISK-001`) rather than labels.

### 2. Version Synchronization (`vaptsecure.php`)
- **Update Version**: Update the `VAPTSECURE_VERSION` constant and plugin header to match the frontend (v2.10.2 or latest).
- **Verify Consistency**: Ensure all version-related constants (`VAPTSECURE_VERSION`, `VAPTC_VERSION`) are aligned.

### 3. Frontend Version and Feature Loading (`assets/js/workbench.js`)
- **Version Display**: Ensure the frontend reads the version from `vaptSecureSettings.pluginVersion`.
- **Feature Filtering**: Investigate why "No released protections found" is shown. This usually happens if the features fetched from the REST API don't match the expected status (`Release`) or if the `generated_schema` is missing/empty.
- **REST API Scoping**: Ensure that in a generated build (where `VAPTSECURE_DOMAIN_LOCKED` is defined), the feature list is correctly scoped to what's defined in the config.

### 4. REST API Scoping (`includes/class-vaptsecure-rest.php`)
- **Filter Features**: Update `get_features` to respect `VAPTSECURE_RESTRICT_FEATURES` and only return features that are allowed via `vaptsecure_is_feature_allowed()`.

## Verification Plan

### Automated Tests
- Run PHPUnit tests for `VAPTSECURE_Build` to verify config generation.
- Test `vaptsecure_is_feature_allowed()` with various constant configurations.

### Manual Verification
- Generate a build with "Restrict Features" disabled.
- Inspect the generated `.php` config file to ensure all features are listed.
- Install the generated build on a test site.
- Verify the version displayed in the dashboard matches the plugin header.
- Verify that features are listed in the dashboard and their controls are accessible.
