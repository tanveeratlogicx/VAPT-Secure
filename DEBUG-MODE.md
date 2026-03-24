# VAPT Secure Debug Mode Documentation

## Overview

VAPT Secure includes a comprehensive debug logging system that helps developers troubleshoot issues while keeping production environments clean. The debug system is implemented in both JavaScript (browser console) and PHP (server-side logs).

## JavaScript Debug Control

### Enabling/Disabling Debug Mode

JavaScript debug mode is controlled by the `VAPT_DEBUG` constant defined at the top of each JavaScript module file:

```javascript
const VAPT_DEBUG = false; // Set to true to enable debug logging
```

### Debug Helper Functions

All JavaScript modules use the `vaptLog` helper object for conditional logging:

```javascript
const vaptLog = {
  debug: (message, data) => {
    if (VAPT_DEBUG) {
      console.log('[VAPT DEBUG]', message, data);
    }
  },
  info: (message, data) => {
    if (VAPT_DEBUG) {
      console.info('[VAPT INFO]', message, data);
    }
  },
  warning: (message, data) => {
    if (VAPT_DEBUG) {
      console.warn('[VAPT WARNING]', message, data);
    }
  },
  error: (message, data) => {
    console.error('[VAPT ERROR]', message, data); // Always logged
  }
};
```

### Usage Examples

```javascript
// Debug message (only shown when VAPT_DEBUG = true)
vaptLog.debug('Feature data loaded', featureData);

// Info message (only shown when VAPT_DEBUG = true)
vaptLog.info('API request initiated', { endpoint: '/v1/features' });

// Warning message (only shown when VAPT_DEBUG = true)
vaptLog.warning('Rate limit approaching', { requests: 95, limit: 100 });

// Error message (always shown regardless of debug mode)
vaptLog.error('API request failed', { status: 500, message: 'Internal Server Error' });
```

### Files with Debug Control

- `assets/js/admin.js`
- `assets/js/workbench.js`
- `assets/js/client.js`
- `assets/js/modules/generated-interface.js`
- `assets/js/modules/interface-generator.js`

## PHP Debug Control

### Enabling/Disabling Debug Mode

PHP debug mode is controlled by the `VAPTSECURE_DEBUG` constant. This can be defined in `wp-config.php` or in `vaptsecure.php`:

```php
// In wp-config.php (recommended)
define('VAPTSECURE_DEBUG', true); // Enable debug mode

// Or in vaptsecure.php (for development only)
if (!defined('VAPTSECURE_DEBUG')) {
    define('VAPTSECURE_DEBUG', false);
}
```

### Debug Helper Functions

PHP uses the following debug functions defined in `includes/debug-utils.php`:

```php
/**
 * Conditional debug logging
 * @param string $message The message to log
 * @param string $level The log level (debug, info, warning, error)
 * @param mixed $data Optional data to include in log
 */
function vapt_log($message, $level = 'debug', $data = null);

/**
 * Debug level logging (only when VAPTSECURE_DEBUG = true)
 */
function vapt_debug($message, $data = null);

/**
 * Info level logging (only when VAPTSECURE_DEBUG = true)
 */
function vapt_info($message, $data = null);

/**
 * Warning level logging (only when VAPTSECURE_DEBUG = true)
 */
function vapt_warning($message, $data = null);

/**
 * Error level logging (always logged regardless of debug mode)
 */
function vapt_error($message, $data = null);
```

### Usage Examples

```php
// Debug message (only logged when VAPTSECURE_DEBUG = true)
vapt_debug('Feature status updated', ['feature_key' => 'bot-protection', 'status' => 'active']);

// Info message (only logged when VAPTSECURE_DEBUG = true)
vapt_info('Processing enforcement rules', ['count' => 5]);

// Warning message (only logged when VAPTSECURE_DEBUG = true)
vapt_warning('Rate limit threshold reached', ['requests' => 100, 'window' => 3600]);

// Error message (always logged regardless of debug mode)
vapt_error('Database query failed', ['query' => $sql, 'error' => $wpdb->last_error]);
```

### Log Format

All PHP debug logs are prefixed with `[VAPT]` and include the log level:

```
[VAPT] DEBUG: Feature data loaded {"key":"bot-protection"}
[VAPT] INFO: API request initiated
[VAPT] WARNING: Rate limit approaching
[VAPT] ERROR: Database query failed
```

## Production vs Development

### Production Environment (Recommended)

**JavaScript:** Set `VAPT_DEBUG = false` in all JavaScript files
**PHP:** Set `define('VAPTSECURE_DEBUG', false)` in wp-config.php

This ensures:

- Only critical errors are logged
- Browser console remains clean
- Server logs are not flooded with debug information
- Better performance (no unnecessary string operations)

### Development Environment

**JavaScript:** Set `VAPT_DEBUG = true` in JavaScript files you're debugging
**PHP:** Set `define('VAPTSECURE_DEBUG', true)` in wp-config.php

This provides:

- Full visibility into application flow
- Detailed data structures in logs
- Easier troubleshooting of complex issues
- Better understanding of feature interactions

## Viewing Debug Logs

### JavaScript Console Logs

1. Open browser Developer Tools (F12)
2. Navigate to Console tab
3. Filter by `[VAPT]` to see only VAPT Secure logs
4. Look for color-coded messages:
   - Blue: Debug messages
   - Green: Info messages
   - Yellow: Warning messages
   - Red: Error messages

### PHP Error Logs

WordPress error logs are typically located at:

- `wp-content/debug.log` (if WP_DEBUG is enabled)
- Server error logs (location varies by hosting)
- Plugin-specific logs (if configured)

To enable WordPress debug logging:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## Troubleshooting

### Issue: No debug logs appearing

**JavaScript:**

- Verify `VAPT_DEBUG = true` is set in the file
- Check browser console is open and not filtered
- Ensure no browser extensions are blocking console output

**PHP:**

- Verify `VAPTSECURE_DEBUG` is defined before plugin loads
- Check `debug-utils.php` is included in `vaptsecure.php`
- Verify WordPress error logging is enabled
- Check file permissions on log files

### Issue: Too many logs in production

**Solution:**

- Ensure `VAPT_DEBUG = false` in all JavaScript files
- Ensure `VAPTSECURE_DEBUG = false` in wp-config.php
- Check for any hardcoded `console.log` or `error_log` calls that bypass the debug system

### Issue: 500 Internal Server Error after enabling debug

**Solution:**

- Check that `debug-utils.php` is loaded via `plugins_loaded` hook
- Verify WordPress is fully initialized before debug functions are called
- Check for syntax errors in debug-utils.php
- Review server error logs for specific error message

## Best Practices

1. **Never commit debug mode enabled to production**
   - Always set `VAPT_DEBUG = false` before committing
   - Use environment variables or configuration files for debug settings

2. **Use appropriate log levels**
   - `debug`: Detailed information for development
   - `info`: General informational messages
   - `warning`: Potential issues that don't stop execution
   - `error`: Critical issues that need immediate attention

3. **Include context in log messages**
   - Add relevant data structures to help with debugging
   - Use descriptive message text
   - Include timestamps when relevant

4. **Clean up debug statements**
   - Remove debug logs after fixing issues
   - Don't leave temporary debug code in production
   - Use the debug helper functions consistently

## File Structure

```
VAPT-Secure/
├── assets/js/
│   ├── admin.js                    (VAPT_DEBUG control)
│   ├── workbench.js                 (VAPT_DEBUG control)
│   ├── client.js                    (VAPT_DEBUG control)
│   └── modules/
│       ├── generated-interface.js      (VAPT_DEBUG control)
│       └── interface-generator.js     (VAPT_DEBUG control)
├── includes/
│   └── debug-utils.php              (PHP debug helpers)
└── vaptsecure.php                  (Loads debug-utils.php)
```

## Version History

- **v2.5.9** - Implemented comprehensive debug control system
  - Added vaptLog helper for JavaScript
  - Added vapt_* functions for PHP
  - Replaced all console.log and error_log calls
  - Added conditional logging based on debug mode

## Support

For issues or questions about the debug system:

1. Check this documentation first
2. Review the debug-utils.php file for function definitions
3. Check browser/server logs for specific error messages
4. Ensure WordPress and PHP meet minimum requirements

---

**Last Updated:** March 23, 2026  
**Plugin Version:** 2.5.9
