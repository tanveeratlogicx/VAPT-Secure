# CRITICAL FIX v3.13.27: Risk-004 Using Wrong Driver (.htaccess Instead of Hook)

## Problem Identified

Risk-004's generated schema incorrectly has **`driver: htaccess`** instead of **`driver: hook`**, causing:

1. ❌ Placeholder comment in .htaccess: `# Protection logic should be provided via remediation field`
2. ❌ No actual .htaccess rules generated (because mappings contain PHP code, not Apache rules)
3. ❌ Verification fails (looking for htaccess markers that don't exist)
4. ❌ PHP Functions code written to external file but never used

## Root Cause

The schema generation process is assigning `.htaccess` as the driver for RISK-004, even though:
- The enforcer pattern is `php_functions` 
- The implementation code contains `add_action('login_init', ...)`
- There are no actual .htaccess rules to generate

## Solution Implemented

### Fix 1: Auto-Correct Driver in REST API
**File:** `includes/class-vaptsecure-rest.php`

Added detection and correction when verifying:

```php
// [v3.13.27] CRITICAL: Force hook driver for PHP Functions features
// If schema says htaccess but mappings contain PHP code, switch to hook
if ($needs_php_functions && ($driver === 'htaccess' || $driver === '')) {
  error_log("VAPT Intelligence: AUTO-CORRECTING driver from '$driver' to 'hook' for feature $feature_key (PHP Functions detected).");
  $schema['enforcement']['driver'] = 'hook';
  $driver = 'hook';
}
```

### Fix 2: Skip .htaccess for PHP Functions Features
**File:** `includes/class-vaptsecure-enforcer.php`

Added detection during enforcement dispatch:

```php
// [v3.13.27] Skip htaccess for PHP Functions features (use hook instead)
if ($driver === 'htaccess' && !empty($schema['enforcement']['mappings'])) {
  $mappings = $schema['enforcement']['mappings'];
  foreach ($mappings as $key => $value) {
    $val_to_test = is_string($value) ? $value : '';
    if (strpos($val_to_test, 'add_action(') !== false ||
        strpos($val_to_test, 'add_filter(') !== false ||
        strpos($val_to_test, 'function ') !== false) {
      error_log("VAPT: Skipping htaccess for $feature_key - using hook driver instead");
      $driver = 'hook';
      $schema['enforcement']['driver'] = 'hook';
      break;
    }
  }
}
```

## How It Works Now

### Before (Broken):
```
RISK-004 Schema → driver: htaccess
  → .htaccess gets placeholder comment
  → No actual rules (PHP code can't go in .htaccess)
  → Verification looks for htaccess markers → FAILS
```

### After (Fixed):
```
RISK-004 Schema → driver: htaccess (wrong)
  → AUTO-CORRECT to driver: hook ✅
  → Skips .htaccess generation
  → Applies via Hook Driver at runtime
  → PHP Functions file loaded
  → Hook registered in WordPress
  → Verification SUCCEEDS ✅
```

## Impact

This fix ensures that **ANY feature with PHP Functions patterns** will automatically use the correct driver, even if the schema is misconfigured.

Applies to:
- ✅ RISK-004: Email Flooding via Password Reset
- ✅ RISK-039: Plugin Version Exposed
- ✅ Any future php_functions enforcer features

## Files Modified

1. ✅ `includes/class-vaptsecure-rest.php` - Auto-correct during verification
2. ✅ `includes/class-vaptsecure-enforcer.php` - Skip htaccess, force hook driver

## Testing Steps

1. Enable RISK-004
2. Save configuration
3. Check .htaccess - should NOT have RISK-004 placeholder anymore
4. Refresh page
5. Verify "Code Injected to vapt-functions.php" tooltip appears
6. Verify "Verified" badge shows

## Expected Behavior

### .htaccess File:
- ❌ **Before:** Contains `# Protection logic should be provided via remediation field` for RISK-004
- ✅ **After:** NO RISK-004 entry (uses PHP hooks instead)

### UI Display:
- ✅ Toggle shows ON
- ✅ "Code Injected to vapt-functions.php" indicator
- ✅ "Verified" badge visible
- ✅ No sync errors

## Version
**v3.13.27** - Critical Driver Auto-Correction

---

**Note:** This is a runtime auto-correction. For a permanent fix, the schema generation process should be updated to assign the correct driver initially.
