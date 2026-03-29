## [2.10.0] - 2026-03-29

### Extensibility & Driver Architecture
- **Driver Interface**: Defined `VAPTSECURE_Driver_Interface` to standardize all enforcement platforms (Apache, Nginx, PHP, etc.).
- **Interface Implementation**: Updated all 11 existing enforcers to implement the new driver contract.
- **Schema Validation Pipeline**: Extracted schema validation, sanitization, and analysis logic into a standalone `VAPTSECURE_Schema_Validator` service.
- **Shared Validation**: Enabled both REST API and Build generator to use the same intelligent enforcement strategy and validation logic.
- **Core Refactoring**: Reduced complexity in `VAPTSECURE_REST` by delegating validation tasks to the new service.

## [2.9.0] - 2026-03-29

### Quality Infrastructure & Development Standards
- **PHP Coding Standards**: Added `squizlabs/php_codesniffer` with `wp-coding-standards` and `phpcs.xml` configuration.
- **Static Analysis**: Integrated `phpstan/phpstan` at level 5 with `phpstan.neon` configuration for WordPress.
- **PHP Unit Testing**: Added PHPUnit 9.6 with mock-based bootstrap and initial unit tests for core logic (Workflow, Enforcer, Build).
- **JavaScript Testing**: Integrated Jest with `jsdom` and added initial tests for UI logic (Interface/APlus generators).
- **CI/CD Readiness**: Added `composer.json` and `package.json` scripts for automated linting, analysis, and testing.
- **Documentation**: Updated `CLAUDE.md` with comprehensive quality tool commands.

## [2.8.0] - 2026-03-29

### REST API & Architecture Enhancements
- **REST API Expansion**: Added new endpoints for license management, settings restoration, and global enforcement.
- **Global Enforcement Logic**: Improved global enforcement with immediate configuration rebuild trigger.
- **License Management**: Enhanced license info retrieval and manual cache restoration capabilities.
- **Build Generator**: Refined file exclusion patterns and directory handling for more robust builds.
- **Database Schema**: Optimized schema update logic and normalization of feature relationships.
- **Modularization**: Continued effort in modularizing REST controllers and shared utilities.

## [2.7.0] - 2026-03-28

### Feature Toggle for Configuration File

- **Build Generator Enhancement**: Added toggle to conditionally include/exclude Active Features list from generated config
- **Restrictive Mode (Toggle ON)**: Config includes feature definitions, plugin ONLY allows listed features
- **Open Mode (Toggle OFF)**: Config excludes feature definitions, plugin allows ANY feature
- **UI Changes**: Added "Restrict to Selected Features" toggle in Build Generator tab
- **API Changes**: Added `restrict_features` parameter to `/build/generate` and `/build/save-config` endpoints
- **Config Generation**: Updated `generate_config_content()` to conditionally include feature definitions
- **Feature Validation**: Added `vaptsecure_is_feature_allowed()` function to check if features are permitted
- **Backward Compatibility**: Default is OFF (Open Mode) for flexible environments

## [2.6.4] - 2026-03-28

### Architecture Improvements: REST API Modularization

- **REST API Controller Split**: Decomposed monolithic `class-vaptsecure-rest.php` (2,791 lines) into focused controllers
- **Base Controller**: Created `class-vaptsecure-rest-base.php` with shared authentication, validation, and helper methods
- **Domain-Specific Controllers**: 
  - `class-vaptsecure-rest-features.php` - Feature management endpoints
  - `class-vaptsecure-rest-domains.php` - Domain CRUD and feature assignment
  - `class-vaptsecure-rest-builds.php` - Build generation and config sync
  - `class-vaptsecure-rest-data-files.php` - JSON file upload and management
  - `class-vaptsecure-rest-security.php` - Security stats and cache clearing
  - `class-vaptsecure-rest-license.php` - License status and validation
  - `class-vaptsecure-rest-settings.php` - Global enforcement and settings
- **Router Pattern**: Main `class-vaptsecure-rest.php` now serves as router (~100 lines) delegating to controllers
- **Backward Compatibility**: All existing REST endpoints remain at same paths with same functionality
- **Improved Maintainability**: Each controller focused on specific domain with clear separation of concerns

## [2.6.3] - 2026-03-28

### Database Migration System

- **Versioned Migration Runner**: Created comprehensive migration system with `VAPTSECURE_Migrations` class
- **Migration Tracking Table**: Added `vaptsecure_migrations` table to track applied migrations
- **Ordered Migration Sequence**: Implemented 27 migrations preserving existing schema evolution
- **Idempotent Migrations**: Each migration checks if changes are needed before applying
- **Replaced Scattered ALTER TABLE**: Consolidated all database modifications into versioned system
- **Updated Activation Hook**: `vaptsecure_activate_plugin()` now uses migration runner
- **Updated Manual Migration Handler**: `vaptsecure_run_manual_migrations()` uses migration system
- **Removed Redundant Functions**: Eliminated `vaptsecure_manual_db_fix()` function

### Migration Sequence
001_create_domains_table, 002_create_domain_features_table, 003_create_feature_status_table, 
004_create_feature_meta_table, 005_create_feature_history_table, 006_create_domain_builds_table, 
007_create_security_events_table, 008_add_is_enabled_to_feature_meta, 009_add_is_enforced_to_feature_meta,
010_add_active_enforcer_to_feature_meta, 011_add_wireframe_url_to_feature_meta, 012_add_generated_schema_to_feature_meta,
013_add_implementation_data_to_feature_meta, 014_add_dev_instruct_to_feature_meta, 015_add_is_adaptive_deployment_to_feature_meta,
016_add_override_schema_to_feature_meta, 017_add_override_impl_data_to_feature_meta, 018_add_manual_expiry_to_domains,
019_add_assigned_to_to_feature_status, 020_normalize_status_enum_to_title_case, 021_add_license_scope_to_domains,
022_add_installation_limit_to_domains, 023_add_id_pk_to_domains, 024_add_include_verification_engine_to_meta,
025_add_include_verification_guidance_to_meta, 026_add_include_manual_protocol_to_meta, 027_add_include_operational_notes_to_meta

## [2.6.2] - 2026-03-28

### Bug Fixes

- **Fixed duplicate vaptsecure_manual_db_fix() function**: Renamed second definition to `vaptsecure_run_manual_migrations()` and removed `function_exists()` guard
- **Fixed undefined $col_dev variable**: Added missing column check for `dev_instruct` column in migration handler
- **Fixed undefined $charset_collate variable**: Added `$charset_collate = $wpdb->get_charset_collate();` in migration handler
- **Extracted duplicated config cleaning logic**: Consolidated `clean_all_config_files()` methods from Enforcer and License Manager classes into shared `VAPTSECURE_Config_Cleaner` class

## [2.6.1] - 2026-03-27

### Bug Fixes

- **Build Generator Data Folder Fix**: Fixed "Include Active Data" toggle not including files from `data/` and `data/Enforcers/` folders
  - Fixed: Root `data` directory now properly created when "Include Active Data" is enabled
  - Fixed: `data/Enforcers` subdirectory now properly included in builds
  - Fixed: Top-level JSON files in data folder (e.g., `ai_agent_instructions_v2.0.json`, `interface_schema_v2.0.json`) now included
  - Fixed: Enforcer template files (e.g., `apache-template.json`, `nginx-template.json`) now included
  - Fixed: Added `!$item->isDir()` check to prevent directories from being blocked by top-level file check
  - Fixed: Added recursive flag to `mkdir()` for proper directory creation

## [2.6.0] - 2026-03-27

### Build Generator Enhancements

- **File Exclusions**: Added global exclusion for debug*, search*, and *.zip files from all builds
  - Excludes: `debug-field-mapping.js`, `debug-field-structure.js`, `vapt-debug.txt`
  - Excludes: `search-enforcer-fields.js`
  - Excludes: All ZIP archives globally (e.g., `vapt-secure-vaptsecure-1.0.1.zip`)
- **Data Folder Handling**: Enhanced "Include Active Data" toggle functionality
  - Includes top-level JSON files from `data/` folder when enabled
  - Includes JSON template files from `data/Enforcers/` directory when enabled
  - Excludes ZIP files within data folder even when "Include Active Data" is enabled
  - Excludes WIP directory and other development subdirectories
- **Configuration File Handling**: Improved config file logic
  - Existing `vapt-*-config-*.php` files are excluded from builds
  - New config file `vapt-{domain}-config-{version}.php` is generated when "Include Config" toggle is enabled
  - Config-only builds properly handle configuration files
  - Configuration files are not tied to Feature IDs

# VAPT Secure Version History

## 2.5.9 (2026-03-21)

- Fixed: Verification now shows other features using same protection with truncated list
- Fixed: Protection properly removed message now includes RiskIDs of other affected features
- Improved: List now limits display to 5 features with "+X more" indicator

## 2.5.8 (2026-03-20)

- Initial release with Global Implementation Fixes (Risk-012)
