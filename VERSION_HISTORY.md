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
