# Build Generator File Exclusion & Inclusion Plan

## Overview

This plan outlines the modifications needed for the VAPTSecure Build Generator to properly handle file exclusions and inclusions based on user requirements.

## Current State Analysis

### Current File Structure

The Build Generator (`includes/class-vaptsecure-build.php`) currently:

1. **Excludes**: Development directories, AI config dirs, test files, and domain-specific config files
2. **Includes**: Plugin core files with conditional data folder handling
3. **Generates**: Configuration file when "Include Config" toggle is enabled

### Issues Identified

1. **Missing exclusions**: No exclusion for `debug*`, `search*`, or `*.zip` files
2. **Data folder handling**: When "Include Active Data" is enabled, only includes specific active data file, not top-level data files
3. **Configuration file logic**: Existing `vapt-*-config-*.php` files are always excluded, but when "Include Config" is enabled, a new config file should be generated and included

## Requirements

### 1. File Exclusions (Always)

- **`debug*` files**: `debug-field-mapping.js`, `debug-field-structure.js`, `vapt-debug.txt`
- **`search*` files**: `search-enforcer-fields.js`
- **`*.zip` files**: All ZIP archives globally (e.g., `vapt-secure-vaptsecure-1.0.1.zip`, `VAPT_AI_Agent_SixTee12.zip`)
- **Test files**: Already handled (`test-*` pattern)

### 2. Data Folder Handling (When "Include Active Data" is ENABLED)

- **Include**: Top-level files from `data/` folder (JSON files like `ai_agent_instructions_v2.0.json`)
- **Include**: Files from `data/Enforcers/` subdirectory (template JSON files)
- **Exclude**: Any `*.zip` files within data folder
- **Exclude**: WIP directory and other subdirectories

### 3. Configuration File Handling

- **When "Include Config" is DISABLED**: Exclude all `vapt-*-config-*.php` files
- **When "Include Config" is ENABLED**:
  - Exclude existing `vapt-*-config-*.php` files
  - Generate and include new configuration file with format: `vapt-{domain}-config-{version}.php`
  - Configuration file should NOT be tied to Feature IDs

## Implementation Plan

### Phase 1: Modify `copy_plugin_files()` Method

#### 1.1 Add New Exclusion Patterns

Add to the `$exclusions` array in `copy_plugin_files()` method:

```php
// Add to existing exclusions array (around line 131-144)
$exclusions = [
    // ... existing exclusions ...
    'debug-field-mapping.js', 'debug-field-structure.js', 'vapt-debug.txt',
    'search-enforcer-fields.js',
];
```

#### 1.2 Add ZIP File Exclusion

Add ZIP file pattern matching before the file copying logic:

```php
// Add after line 192 (after config file exclusion check)
// Exclude ZIP files globally
if (preg_match('/\.zip$/i', $filename)) {
    continue;
}
```

#### 1.3 Modify Data Folder Handling

Replace current data folder logic (lines 164-170) with enhanced logic:

```php
// Handle Data Directory
if (strpos($subPath, 'data') === 0) {
    // When active data file is specified (Include Active Data enabled)
    if ($active_data_file) {
        // Allow specific active data file
        if (strpos($subPath, 'data\\' . $active_data_file) !== false || 
            strpos($subPath, 'data/' . $active_data_file) !== false) {
            // Allow this specific file
        } 
        // Allow top-level JSON files in data folder
        elseif (strpos($subPath, 'data/') === 0 && strpos($subPath, '/') === false) {
            // Check if it's a JSON file (not ZIP)
            if (preg_match('/\.json$/i', $filename)) {
                // Allow top-level JSON files
            } else {
                continue;
            }
        }
        // Allow Enforcers directory files
        elseif (strpos($subPath, 'data/Enforcers/') === 0) {
            // Allow JSON template files in Enforcers
            if (preg_match('/\.json$/i', $filename)) {
                // Allow Enforcer template files
            } else {
                continue;
            }
        }
        // Exclude everything else in data folder
        else {
            continue;
        }
    } 
    // When no active data file (Include Active Data disabled)
    else {
        continue; // Skip entire data folder
    }
}
```

#### 1.4 Update Configuration File Exclusion Logic

Modify the config file exclusion (lines 189-192) to be conditional:

```php
// Exclude domain-specific configuration files (vapt-*-config-*.php)
// Only exclude if we're not in a config-only build
if (preg_match('/^vapt-.*-config-.*\.php$/i', $filename)) {
    // Check if this is a dynamically generated config file
    // We'll need to pass a parameter to identify generated vs existing files
    continue;
}
```

### Phase 2: Modify `generate()` Method

#### 2.1 Pass Build Type to `copy_plugin_files()`

Update the call to `copy_plugin_files()` to include build type information:

```php
// Line 67: Modify to pass additional parameters
self::copy_plugin_files(VAPTSECURE_PATH, $plugin_dir, $active_data_file_name, $generate_type, $data);
```

#### 2.2 Update Configuration File Generation Logic

The current logic (lines 70-73) is correct:

```php
// 4. Inject Config File (If Requested)
if (!isset($data['include_config']) || $data['include_config'] === true || $data['include_config'] === 'true' || $data['include_config'] === 1) {
    $config_filename = "vapt-{$domain}-config-{$version}.php";
    file_put_contents($plugin_dir . "/" . $config_filename, $config_content);
}
```

### Phase 3: Update Method Signature

#### 3.1 Update `copy_plugin_files()` Method Signature

```php
private static function copy_plugin_files($source, $dest, $active_data_file = null, $generate_type = 'full_build', $build_data = [])
```

#### 3.2 Add Build Data Parameter Handling

At the beginning of `copy_plugin_files()`:

```php
$include_config = isset($build_data['include_config']) && 
                  ($build_data['include_config'] === true || 
                   $build_data['include_config'] === 'true' || 
                   $build_data['include_config'] === 1);
```

#### 3.3 Update Configuration File Exclusion with Context

```php
// Exclude domain-specific configuration files (vapt-*-config-*.php)
// But allow if this is a config-only build or config is included
if (preg_match('/^vapt-.*-config-.*\.php$/i', $filename)) {
    // Skip exclusion if this is a config-only build
    if ($generate_type === 'config_only') {
        // Allow config files in config-only builds
    } 
    // Skip exclusion if config is being included (we'll generate a new one)
    elseif ($include_config) {
        // Still exclude existing config files, new one will be generated
        continue;
    } 
    // Default: exclude config files
    else {
        continue;
    }
}
```

### Phase 4: Clean Up Temporary Script

#### 4.1 Remove `add_zip_exclusion.php`

Delete the temporary script as it will be replaced by proper implementation.

## Code Changes Summary

### File: `includes/class-vaptsecure-build.php`

#### Section 1: Method Signature Update

```php
private static function copy_plugin_files($source, $dest, $active_data_file = null, $generate_type = 'full_build', $build_data = [])
```

#### Section 2: Variable Initialization

```php
$include_config = isset($build_data['include_config']) && 
                  ($build_data['include_config'] === true || 
                   $build_data['include_config'] === 'true' || 
                   $build_data['include_config'] === 1);
```

#### Section 3: Exclusions Array Update

```php
$exclusions = [
    '.git', '.vscode', 'node_modules', 'brain', 'tests', 'vapt-debug.txt',
    'Implementation Plan', 'plans', 'tools', 'archive', 'Debug', 'backup_debug_cleanup',
    // AI/Agent configuration directories
    '.ai', '.roo', '.claude', '.cursor', '.gemini', '.kilocode', '.qoder', '.trae',
    '.windsurf', '.opencode', '.agent', '.kilo',
    // Specific AI subdirectories
    '.ai/workflows', '.ai/skills', '.ai/rules',
    '.claude/skills', '.cursor/skills', '.gemini/antigravity/skills',
    '.kilocode/rules', '.qoder/skills', '.trae/skills', '.windsurf/skills',
    '.roo/rules', '.roo/skills',
    // Deployment directory
    'deployment',
    // Debug and search files
    'debug-field-mapping.js', 'debug-field-structure.js', 'search-enforcer-fields.js'
];
```

#### Section 4: ZIP File Exclusion

```php
// Exclude ZIP files globally (add after config file exclusion)
if (preg_match('/\.zip$/i', $filename)) {
    continue;
}
```

#### Section 5: Enhanced Data Folder Handling

```php
// Handle Data Directory
if (strpos($subPath, 'data') === 0) {
    // When active data file is specified (Include Active Data enabled)
    if ($active_data_file) {
        // Allow specific active data file
        $active_file_allowed = false;
        if (strpos($subPath, 'data\\' . $active_data_file) !== false || 
            strpos($subPath, 'data/' . $active_data_file) !== false) {
            $active_file_allowed = true;
        } 
        // Allow top-level JSON files in data folder
        elseif (strpos($subPath, 'data/') === 0 && substr_count($subPath, '/') === 1) {
            // Check if it's a JSON file (not ZIP)
            if (preg_match('/\.json$/i', $filename)) {
                $active_file_allowed = true;
            }
        }
        // Allow Enforcers directory files
        elseif (strpos($subPath, 'data/Enforcers/') === 0) {
            // Allow JSON template files in Enforcers
            if (preg_match('/\.json$/i', $filename)) {
                $active_file_allowed = true;
            }
        }
        
        if (!$active_file_allowed) {
            continue;
        }
    } 
    // When no active data file (Include Active Data disabled)
    else {
        continue; // Skip entire data folder
    }
}
```

#### Section 6: Configuration File Exclusion with Context

```php
// Exclude domain-specific configuration files (vapt-*-config-*.php)
if (preg_match('/^vapt-.*-config-.*\.php$/i', $filename)) {
    // In config-only builds, allow all config files
    if ($generate_type === 'config_only') {
        // Allow - config files are the purpose of this build
    } 
    // When config inclusion is enabled, still exclude existing ones
    // (a new one will be generated in the generate() method)
    else {
        continue;
    }
}
```

#### Section 7: Update Method Call

```php
// Line 67 in generate() method
self::copy_plugin_files(VAPTSECURE_PATH, $plugin_dir, $active_data_file_name, $generate_type, $data);
```

## Testing Plan

### Test Cases

#### Test 1: Default Build (No Data, No Config)

- **Input**: `include_data` = false, `include_config` = false
- **Expected**:
  - No data folder files included
  - No config files included
  - No debug/search/ZIP files included
  - Plugin core files included

#### Test 2: Build with Active Data

- **Input**: `include_data` = true, `include_config` = false
- **Expected**:
  - Active data file included (e.g., `Feature-List-99.json`)
  - Top-level JSON files in `data/` included
  - `data/Enforcers/` JSON files included
  - ZIP files in data folder excluded
  - WIP directory excluded

#### Test 3: Build with Config

- **Input**: `include_data` = false, `include_config` = true
- **Expected**:
  - New config file generated: `vapt-{domain}-config-{version}.php`
  - Existing config files excluded
  - Data folder excluded

#### Test 4: Config-Only Build

- **Input**: `generate_type` = 'config_only'
- **Expected**:
  - Only configuration file generated
  - No other files copied

#### Test 5: Full Build with All Options

- **Input**: `include_data` = true, `include_config` = true
- **Expected**:
  - Active data file included
  - Top-level data JSON files included
  - Enforcers JSON files included
  - New config file generated
  - No debug/search/ZIP files included

## Files to Be Excluded/Included

### Always Excluded (Global)

- `debug-field-mapping.js`
- `debug-field-structure.js`
- `vapt-debug.txt`
- `search-enforcer-fields.js`
- Any file matching `*.zip`
- Files starting with `test-`
- Existing `vapt-*-config-*.php` files (unless config-only build)

### Included When "Include Active Data" Enabled

- Active data file (e.g., `Feature-List-99.json`)
- Top-level JSON files in `data/` folder:
  - `ai_agent_instructions_v2.0.json`
  - `enforcer_pattern_library_v2.0.json`
  - `interface_schema_v2.0.json`
  - `vapt_driver_manifest_v2.0.json`
- JSON files in `data/Enforcers/`:
  - `apache-template.json`
  - `caddy-template.json`
  - `fail2ban-template.json`
  - `htaccess-template.json`
  - `nginx-template.json`
  - `php-functions-template.json`
  - `server-cron-template.json`
  - `wordpress-template.json`
  - `wp-config-template.json`

### Excluded from Data Folder Even When Enabled

- `data/VAPT_AI_Agent_SixTee12.zip` (ZIP file)
- `data/VAPT-Risk-Catalogues-Copy.zip` (ZIP file)
- `data/VAPT-Schema-First-v1.0.zip` (ZIP file)
- `data/VAPT-Schema-First-v1.1.zip` (ZIP file)
- `data/VAPT-Schema-First-v1.2.zip` (ZIP file)
- `data/VAPT-Schema-v2.0_ValidatesWell_v1.9.1.zip` (ZIP file)
- `data/WIP/` directory (development work)
- `data/Docs_Notes/` directory

## Version Updates

### Update Plugin Version

The plugin version should be incremented after implementation:

- Current: 2.5.29/2.5.30
- New: 2.5.31 (or appropriate version bump)

### Update Version History

Add entry to `VERSION_HISTORY.md`:

```
## 2.5.31 - 2026-03-27
### Build Generator Enhancements
- Added exclusion for debug*, search*, and *.zip files from builds
- Enhanced data folder handling when "Include Active Data" is enabled
- Proper configuration file generation when "Include Config" is enabled
- Fixed ZIP file exclusion in data folder
```

## Risk Assessment

### Low Risk Changes

- Adding new exclusion patterns (debug*, search*, *.zip)
- These files are not required for plugin functionality

### Medium Risk Changes

- Modifying data folder inclusion logic
- Requires testing to ensure active data file is properly included

### High Risk Changes

- Configuration file exclusion logic
- Must ensure new config files are generated when needed
- Must not break config-only builds

## Rollback Plan

If issues arise:

1. Revert changes to `includes/class-vaptsecure-build.php`
2. Restore from git: `git checkout includes/class-vaptsecure-build.php`
3. The temporary `add_zip_exclusion.php` script can be used as reference

## Implementation Timeline

1. **Phase 1**: Code modifications (1-2 hours)
2. **Phase 2**: Testing (1 hour)
3. **Phase 3**: Documentation updates (30 minutes)
4. **Phase 4**: Deployment and verification (30 minutes)

## Success Criteria

1. Builds generated without debug/search/ZIP files
2. Data folder properly included when "Include Active Data" enabled
3. Configuration files correctly handled based on toggle state
4. No regression in existing functionality
5. All test cases pass

## Next Steps

1. Review this plan with stakeholders
2. Implement code changes
3. Test all build scenarios
4. Update documentation
5. Deploy to production
