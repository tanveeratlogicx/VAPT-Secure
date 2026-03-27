# Feature Toggle for Configuration File - Implementation Plan

## Overview

This plan documents the enhancement request to add a toggle in the Build Generator for conditionally including/excluding the Active Features list from the generated configuration file.

## Current Behavior

- The config file always includes Active Features as `define()` statements:

  ```php
  define( 'VAPTSECURE_FEATURE_RISK_001', true );
  define( 'VAPTSECURE_FEATURE_RISK_002', true );
  // etc.
  ```

## Proposed Enhancement

### Option A: Include Features in Config (RESTRICTIVE MODE)

When the toggle is ON (include features in config):

- The config file contains the feature definitions
- The plugin ONLY allows the listed features to be active
- Custom-developed features are blocked
- Use case: Locked/controlled environments where only approved features should work

### Option B: Exclude Features from Config (OPEN MODE)

When the toggle is OFF (exclude features from config):

- The config file does NOT contain feature definitions
- The plugin allows ANY feature to be active
- Custom-developed features work normally
- Use case: Flexible environments where custom development is encouraged

## Implementation Requirements

### 1. UI Changes (admin.js)

- Add toggle control in Build Generator tab
- Label: "Restrict to Selected Features" or similar
- Default: OFF (exclude features - more flexible)

### 2. API Changes (class-vaptsecure-rest.php)

- Add `restrict_features` parameter to `/build/generate` endpoint
- Pass parameter to build generation

### 3. Config Generation (class-vaptsecure-build.php)

- Conditionally include/exclude the Active Features section based on toggle
- Modify `generate_config_content()` to accept new parameter

### 4. Plugin Logic (vaptsecure.php / other files)

- Check if config has feature restrictions
- If features are defined in config: enforce whitelist
- If features are NOT defined: allow all features

## Files to Modify

1. `assets/js/admin.js` - Add toggle UI
2. `includes/class-vaptsecure-rest.php` - Add API parameter
3. `includes/class-vaptsecure-build.php` - Conditional feature output
4. Plugin core files - Enforce feature restrictions when present

## Notes

- This is a future enhancement, not part of the current bug fix cycle
- Consider backward compatibility for existing builds
- May need database migration for new toggle setting

## Related

- Original bug fix: Build Generator data folder inclusion (v2.6.1)
- Config file generation: `generate_config_content()` method
