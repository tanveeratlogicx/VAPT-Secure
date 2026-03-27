# VAPT-Secure Plugin - Comprehensive Execution Plan

> Generated: 2026-03-27 | Version: 2.6.1 → 2.7.0

---

## P0: Critical Bug Fixes

### P0.1: Fix Duplicate `vaptsecure_manual_db_fix()` Function

**Problem**: Two definitions of `vaptsecure_manual_db_fix()` exist in `vaptsecure.php`:
- Definition 1 (line 319): Simple 3-column check, registered on `init` hook (line 371)
- Definition 2 (line 394): Wrapped in `function_exists()`, triggered via `?vaptsecure_fix_db=1`

PHP loads Definition 1 first. The `function_exists()` guard in Definition 2 always returns `true`, so Definition 2 is **never defined**. The `?vaptsecure_fix_db=1` trigger is dead code.

**Fix Strategy**: 
- Rename Definition 2 to `vaptsecure_run_manual_migrations()`
- Remove the `function_exists()` guard (no longer needed since names differ)
- Keep Definition 1 as-is (runs on `init` for lightweight column checks)
- The full migration handler stays accessible via `?vaptsecure_fix_db=1`

**Files Modified**: `vaptsecure.php`

---

### P0.2: Fix Undefined `$col_dev` Variable

**Problem**: At `vaptsecure.php:458`, `$col_dev` is used but never defined:
```php
if (empty($col_dev)) {
    $wpdb->query("ALTER TABLE {$table_meta} ADD COLUMN dev_instruct LONGTEXT DEFAULT NULL");
}
```

**Fix**: Add the missing column check before line 458:
```php
$col_dev = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM {$meta_table} LIKE %s", 'dev_instruct'));
```

**Files Modified**: `vaptsecure.php` (inside the renamed `vaptsecure_run_manual_migrations()`)

---

### P0.3: Fix Undefined `$charset_collate` in Migration Handler

**Problem**: At `vaptsecure.php:509`, `$charset_collate` is used in the Security Events CREATE TABLE statement but is not defined in the function scope.

**Fix**: Add `$charset_collate = $wpdb->get_charset_collate();` at the start of the migration handler function, after `global $wpdb;`.

**Files Modified**: `vaptsecure.php` (inside `vaptsecure_run_manual_migrations()`)

---

### P0.4: Extract Duplicated `clean_all_config_files()` Into Shared Class

**Problem**: Identical config-cleaning logic exists in:
- `class-vaptsecure-enforcer.php:530-592` (public static)
- `class-vaptsecure-license-manager.php:323-377` (private static)

Both clean `.htaccess`, `wp-config.php`, `vapt-functions.php`, `nginx.conf`, `web.config` with identical regex patterns.

**Fix Strategy**:
1. Create `includes/class-vaptsecure-config-cleaner.php` with `VAPTSECURE_Config_Cleaner::clean_all()`
2. Include it in `vaptsecure.php` alongside other includes
3. Replace both duplicate methods with a single call to `VAPTSECURE_Config_Cleaner::clean_all()`
4. The enforcer's `clean_all_config_files()` becomes a thin wrapper: `return VAPTSECURE_Config_Cleaner::clean_all();`
5. The license manager's `clean_all_config_files()` calls the same shared method

**Files Modified**:
- NEW: `includes/class-vaptsecure-config-cleaner.php`
- `vaptsecure.php` (add require_once)
- `includes/class-vaptsecure-enforcer.php` (delegate to cleaner)
- `includes/class-vaptsecure-license-manager.php` (delegate to cleaner)

---

## P1: Database Migration System

### P1.1: Create Versioned Migration Runner

**Problem**: Database migrations are scattered `ALTER TABLE` statements with no versioning, ordering, or idempotency tracking.

**Fix Strategy**:
1. Create `includes/class-vaptsecure-migrations.php` with `VAPTSECURE_Migrations`
2. Define a `vaptsecure_migrations` tracking table (migration_id, applied_at)
3. Each migration as a named method with up/down capability
4. Runner checks which migrations are pending and executes in order
5. Replace all ad-hoc migration code in `vaptsecure_activate_plugin()` and `vaptsecure_run_manual_migrations()` with calls to the migration runner

**Migration Sequence** (preserving existing schema evolution):
```
001_create_domains_table
002_create_domain_features_table
003_create_feature_status_table
004_create_feature_meta_table
005_create_feature_history_table
006_create_domain_builds_table
007_create_security_events_table
008_add_is_enabled_to_feature_meta
009_add_is_enforced_to_feature_meta
010_add_active_enforcer_to_feature_meta
011_add_wireframe_url_to_feature_meta
012_add_generated_schema_to_feature_meta
013_add_implementation_data_to_feature_meta
014_add_dev_instruct_to_feature_meta
015_add_is_adaptive_deployment_to_feature_meta
016_add_override_schema_to_feature_meta
017_add_override_impl_data_to_feature_meta
018_add_manual_expiry_to_domains
019_add_assigned_to_to_feature_status
020_normalize_status_enum_to_title_case
021_add_license_scope_to_domains
022_add_installation_limit_to_domains
023_add_id_pk_to_domains
024_add_include_verification_engine_to_meta
025_add_include_verification_guidance_to_meta
026_add_include_manual_protocol_to_meta
027_add_include_operational_notes_to_meta
```

**Files Modified**:
- NEW: `includes/class-vaptsecure-migrations.php`
- `vaptsecure.php` (replace scattered migrations with runner call)
- Include in activation hook and `?vaptsecure_fix_db=1` handler

---

## P2: Architecture Improvements

### P2.1: Split REST API Into Focused Controllers

**Problem**: `class-vaptsecure-rest.php` (2,791 lines) handles 30+ endpoints across features, domains, builds, data files, security, and license management.

**Fix Strategy**: Create a base controller and domain-specific controllers:

```
includes/rest/
  class-vaptsecure-rest-base.php          (~150 lines) - Base class, auth helpers, schema validation utilities
  class-vaptsecure-rest-features.php      (~600 lines) - get_features, update_feature, transition_feature, verify_implementation, batch_revert, preview_revert
  class-vaptsecure-rest-domains.php       (~400 lines) - get_domains, update_domain, update_domain_features, delete_domain, batch_delete_domains
  class-vaptsecure-rest-builds.php        (~200 lines) - generate_build, save_config_to_root, sync_config_from_file
  class-vaptsecure-rest-data-files.php    (~400 lines) - get_data_files, get_all_data_files, upload_json, remove_data_file, update_hidden_files, update_file_meta, handle_active_file
  class-vaptsecure-rest-security.php      (~200 lines) - get_security_stats, get_security_logs, clear_enforcement_cache
  class-vaptsecure-rest-license.php       (~150 lines) - get_license_status, restore_license_cache, force_license_check
  class-vaptsecure-rest-settings.php      (~150 lines) - get_global_enforcement, update_global_enforcement, upload_media
```

**Compatibility**: The original `class-vaptsecure-rest.php` becomes a thin router that includes all controllers and delegates `register_routes()` to each. All existing REST endpoints remain at the same paths.

**Files Modified**:
- NEW: 8 files in `includes/rest/`
- `includes/class-vaptsecure-rest.php` (slimmed to ~100 lines as router)
- `vaptsecure.php` (update require_once path)

---

### P2.2: Decompose admin.js Into Modular Components

**Problem**: `admin.js` (6,514 lines) contains the entire Domain Admin dashboard as a single IIFE with all JSX inline.

**Fix Strategy**: Extract into modular files while preserving the existing wp.element/WordPress dependency pattern (no build tooling changes):

```
assets/js/
  admin.js                               (~100 lines) - Entry point, imports, renders App
  admin/
    App.jsx                              (~150 lines) - Main layout, tab routing
    FeatureList.jsx                      (~400 lines) - Feature table with sorting/filtering
    FeatureDetail.jsx                    (~600 lines) - Feature edit panel, schema editor
    DomainManager.jsx                    (~500 lines) - Domain CRUD, feature assignment
    BuildGenerator.jsx                   (~300 lines) - Build config, ZIP generation
    SecurityDashboard.jsx                (~250 lines) - Security events, stats
    EnforcementToggles.jsx               (~200 lines) - Enable/disable enforcement controls
    LicenseManager.jsx                   (~200 lines) - License status, renewal
    shared/
      StatusBadge.jsx                    (~50 lines)
      ConfirmDialog.jsx                  (~80 lines)
      DataTable.jsx                      (~150 lines)
      ApiHelper.js                       (~100 lines) - Centralized API calls
```

**Compatibility**: Each file uses `wp.element.createElement` (aliased as `el`) and registers itself on a `window.vaptAdmin` namespace. The entry point loads all modules via `wp_enqueue_script` with dependencies.

**Files Modified**:
- NEW: 12+ files in `assets/js/admin/`
- `assets/js/admin.js` (slimmed to entry point)
- `vaptsecure.php` (update enqueue_script calls to include new module files)

---

## P3: Quality Infrastructure

### P3.1: PHP Linting & Coding Standards

**Strategy**:
1. Add `composer.json` with dev dependencies:
   - `squizlabs/php_codesniffer` + `wp-coding-standards/wpcs`
   - `phpstan/phpstan` for static analysis
2. Add `phpcs.xml` configuration extending WordPress standards
3. Add `phpstan.neon` at level 5 (reasonable for existing codebase)
4. Create `composer.json` scripts for `lint`, `lint:fix`, `analyze`
5. Update CLAUDE.md with lint commands

**Files Modified**:
- NEW: `composer.json`, `phpcs.xml`, `phpstan.neon`
- `CLAUDE.md` (add lint commands)

---

### P3.2: PHP Unit Tests

**Strategy**:
1. Add `phpunit.xml` configuration
2. Create test bootstrap that mocks WordPress functions
3. Start with unit tests for core logic:
   - `VAPTSECURE_Workflow::is_transition_allowed()` (pure logic, easy to test)
   - `VAPTSECURE_Enforcer::extract_code_from_mapping()` (string/array processing)
   - `VAPTSECURE_Build::generate_config_content()` (string generation)
   - Schema validation methods
4. Integration tests require WordPress test harness (document setup)

**Files Modified**:
- NEW: `tests/phpunit.xml`, `tests/bootstrap.php`, `tests/unit/test-workflow.php`, `tests/unit/test-enforcer.php`, `tests/unit/test-build.php`

---

### P3.3: JavaScript Testing

**Strategy**:
1. Add `package.json` with Jest + jsdom
2. Test pure utility functions from modules
3. Focus on `interface-generator.js` and `aplus-generator.js` logic

**Files Modified**:
- NEW: `package.json` (root or assets/js/), test files

---

## P4: Extensibility

### P4.1: Define Driver Interface Contract

**Strategy**:
1. Create `includes/interfaces/interface-vaptsecure-driver.php` defining:
   - `generate_rules($impl_data, $schema): array`
   - `write_batch($rules, $target = 'root'): bool`
   - `clean($target = 'root'): bool`
2. Add interface declaration to all 11 existing drivers
3. Enables adding new drivers (Cloudflare Workers, AWS WAF, etc.) without modifying core

**Files Modified**:
- NEW: `includes/interfaces/interface-vaptsecure-driver.php`
- All 11 files in `includes/enforcers/` (add `implements VAPTSECURE_Driver_Interface`)

---

### P4.2: Extract Schema Validation Pipeline

**Strategy**:
1. Move `sanitize_and_fix_schema()`, `validate_schema()`, `validate_implementation_data()`, `analyze_enforcement_strategy()`, and `translate_url_placeholders()` from `class-vaptsecure-rest.php` into `includes/class-vaptsecure-schema-validator.php`
2. Both REST controllers and Build generator can use the shared validator

**Files Modified**:
- NEW: `includes/class-vaptsecure-schema-validator.php`
- `includes/class-vaptsecure-rest.php` (remove methods, delegate to validator)

---

## P5: Documentation & Developer Experience

### P5.1: API Documentation
- Document all REST endpoints with request/response schemas
- Generate from PHPDoc annotations

### P5.2: Development Setup Guide
- Add `docker-compose.yml` for local WordPress dev environment
- Document `composer install && npm install` workflow
- Add `.env.example` with configuration options

### P5.3: Architecture Decision Records
- Why transients for enforcement caching
- Why state machine for feature lifecycle
- Why multi-driver dispatch pattern

---

## Version Bump Plan

Each phase completion triggers a version bump per `.kilocode/rules/version-bump-policy.md`:
- P0 fixes: 2.6.1 → 2.6.2 (PATCH - bug fixes)
- P1 migration system: 2.6.2 → 2.7.0 (MINOR - new feature)
- P2 architecture: 2.7.0 → 2.8.0 (MINOR - structural improvement)
- P3 quality: 2.8.0 → 2.8.1 (PATCH - tooling, no functional change)
- P4 extensibility: 2.8.1 → 2.9.0 (MINOR - new interfaces)
- P5 documentation: 2.9.0 → 2.9.1 (PATCH - docs)

---

## Execution Checklist

### P0 Phase
- [ ] P0.1: Rename duplicate function, update call sites
- [ ] P0.2: Add `$col_dev` definition
- [ ] P0.3: Add `$charset_collate` definition
- [ ] P0.4: Create shared config cleaner, update both consumers
- [ ] Run `php -l includes/*.php` to verify syntax
- [ ] Bump version to 2.6.2

### P1 Phase
- [ ] P1.1: Create migration class with tracking table
- [ ] P1.2: Define all 27 migrations
- [ ] P1.3: Replace scattered migration code
- [ ] Test migration on fresh install and existing install
- [ ] Bump version to 2.7.0

### P2 Phase
- [ ] P2.1: Create REST controller hierarchy
- [ ] P2.2: Extract admin.js components
- [ ] Verify all existing REST endpoints work
- [ ] Bump version to 2.8.0

### P3 Phase
- [ ] P3.1: Add composer.json, phpcs.xml, phpstan.neon
- [ ] P3.2: Add PHPUnit tests for core logic
- [ ] P3.3: Add Jest tests for JS modules
- [ ] Bump version to 2.8.1

### P4 Phase
- [ ] P4.1: Define and implement driver interface
- [ ] P4.2: Extract schema validator
- [ ] Bump version to 2.9.0

### P5 Phase
- [ ] P5.1-P5.3: Documentation updates
- [ ] Bump version to 2.9.1
