# CRITICAL FIX: Risk-004 Verification Failure - Missing File Loader

## Problem Identified

Risk-004 uses **"PHP Functions" enforcer** which writes code to:
```
{ABSPATH}wp-content/plugins/vapt-protection-suite/vapt-functions.php
```

**BUT this file:**
1. ❌ May not exist (different plugin directory)
2. ❌ Is NOT being loaded/included by VAPT-Secure
3. ❌ Code is written but never executed
4. ❌ Verification fails because hook is never registered

## Root Cause

The `php_functions` enforcer pattern is designed for **external file writing**, but:
- The target plugin `vapt-protection-suite` may not be installed
- Even if it exists, the file needs to be loaded at runtime
- The Hook Driver's `runtime_enforcement()` applies hooks directly in memory, not from files

## Solution: Two-Pronged Fix

### Fix 1: Add Physical File Loader (For External PHP Functions)

**File:** `includes/class-vaptsecure-enforcer.php`

Add logic to load the vapt-functions.php file if it exists:

```php
/**
 * Load external PHP functions file if it exists
 */
private static function load_php_functions_file()
{
    // Check for external vapt-protection-suite plugin
    $external_path = ABSPATH . 'wp-content/plugins/vapt-protection-suite/vapt-functions.php';
    
    if (file_exists($external_path)) {
        require_once $external_path;
        error_log("VAPT: Loaded external vapt-functions.php");
    }
    
    // Also check for local bundled version
    $bundled_path = VAPTSECURE_PATH . 'vapt-functions.php';
    if (file_exists($bundled_path)) {
        require_once $bundled_path;
        error_log("VAPT: Loaded bundled vapt-functions.php");
    }
}
```

Call this in `runtime_enforcement()` before applying hooks:

```php
public static function runtime_enforcement()
{
    
    if (empty($enforced)) return;
    
    require_once VAPTSECURE_PATH . 'includes/enforcers/class-vaptsecure-hook-driver.php';
    
    // [v3.13.27] Load external PHP functions files
    self::load_php_functions_file();
    
    foreach ($enforced as $meta) {
        // ... rest of code ...
    }
}
```

### Fix 2: Update Verification to Check Physical Files

**File:** `includes/enforcers/class-vaptsecure-hook-driver.php`

Enhance verification to check if code exists in physical files:

```php
// [v3.13.27] Check external PHP functions files
$external_paths = array(
    ABSPATH . 'wp-content/plugins/vapt-protection-suite/vapt-functions.php',
    VAPTSECURE_PATH . 'vapt-functions.php'
);

foreach ($external_paths as $file_path) {
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        // Look for the specific function or marker
        if (strpos($content, 'vapt_rate_limit_password_reset') !== false ||
            strpos($content, '// BEGIN VAPT RISK-004') !== false) {
            return true;
        }
    }
}
```

### Fix 3: Alternative - Use Direct Hook Driver Instead of PHP Functions

**Better approach:** Change RISK-004's schema to use `'driver' => 'hook'` directly instead of `'php_functions'` enforcer.

This way:
- Code is applied directly in memory via Hook Driver's `apply()` method
- No external file dependency
- Works immediately with existing runtime enforcement
- Verification will work correctly

## Recommended Action

**Use Fix 3** - Update RISK-004's generated schema to use:
```json
{
  "enforcement": {
    "driver": "hook",
    "mappings": {
      "feat_enabled": "add_action('login_init', 'vapt_rate_limit_password_reset');..."
    }
  }
}
```

This aligns with how other hook-based features work in VAPT-Secure.

## Implementation Priority

1. ✅ **Immediate**: Add file loader (Fix 1) - handles existing deployments
2. ✅ **Short-term**: Update verification (Fix 2) - detects file-based code
3. ✅ **Long-term**: Migrate to hook driver (Fix 3) - cleaner architecture

## Version
v3.13.27 - Critical Fix for PHP Functions Enforcer
