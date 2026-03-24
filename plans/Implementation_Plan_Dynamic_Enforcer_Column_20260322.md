# Dynamic Enforcer Column Fix — Implementation Plan

> **20260322_@1620** — Initial Analysis & Plan Created

---

## Problem Statement

The "Enforcer" column in the Domain Admin table (`vaptsecure-domain-admin`) dynamically picks the best enforcer for each of the 125 features by filtering `platform_implementations` from `interface_schema_v2.0.json` against the detected server capabilities. The pipeline:

1. **PHP** `VAPTSECURE_Environment_Detector` detects server → builds `capabilities` map with keys like `apache_htaccess`, `nginx_config`, `php_functions`, etc.
2. **REST** sends `environment_profile` to frontend
3. **JS** `resolveEnforcer()` in `admin.js:4348` filters each feature's `platform_implementations` against `capabilities` using a `compatibilityMap`

### Root Cause: Key Name Mismatches

The 10 unique platform names in the schema are:
`.htaccess`, `Apache`, `Caddy`, `Cloudflare`, `IIS`, `Nginx`, `PHP Functions`, `Server Cron`, `fail2ban`, `wp-config.php`

But the JS `compatibilityMap` and PHP `capability_matrix` use **different internal key names** and have **incomplete mappings**.

---

## Detailed Analysis

### 1. JS `compatibilityMap` (admin.js:4353)

```js
const compatibilityMap = {
  'apache_htaccess': ['.htaccess', 'Apache', 'Litespeed'],
  'nginx_config':    ['Nginx'],
  'iis_config':      ['IIS'],
  'php_functions':   ['PHP Functions', 'WordPress', 'WordPress Core', 'wp-config.php'],
  'cloudflare_edge': ['Cloudflare'],
  'server_cron':     ['Server Cron'],
  'caddy_native':    ['Caddy'],
  'fail2ban':        ['fail2ban']
};
```

**Current Issues:**
- ✅ `.htaccess` → correctly mapped via `apache_htaccess`
- ✅ `Apache` → correctly mapped via `apache_htaccess`
- ✅ `Caddy` → mapped via `caddy_native` — **BUT `caddy_native` is never in `capabilities`** (see PHP issue below)
- ✅ `Cloudflare` → mapped via `cloudflare_edge`
- ✅ `IIS` → mapped via `iis_config`
- ✅ `Nginx` → mapped via `nginx_config`
- ✅ `PHP Functions` → mapped via `php_functions`
- ✅ `Server Cron` → mapped via `server_cron` — **BUT `server_cron` may not be in `capabilities`** depending on detection
- ✅ `fail2ban` → mapped via `fail2ban`
- ✅ `wp-config.php` → mapped via `php_functions`

The **JS map is actually correct** in terms of key-to-platform-name mapping.

### 2. PHP `capability_matrix` (environment-detector.php:43)

```php
'capability_matrix' => [
  'cloudflare_edge'  => [...detected_by cloudflare headers...],
  'nginx_config'     => [...detected_by nginx...],
  'apache_htaccess'  => [...detected_by apache/litespeed...],
  'iis_config'       => [...detected_by iis...],
  'fail2ban'         => [...detected_by php_sapi_detection:any...],
  'server_cron'      => [...detected_by php_sapi_detection:any...],
  'php_functions'    => [...detected_by php_sapi_detection:any (always matches)...]
];
```

> [!CAUTION]
> **`caddy_native` is completely MISSING from the PHP `capability_matrix`!** This means when a Caddy server is detected, `caddy_native` is never added to capabilities, and `Caddy` features ALWAYS show `-`.

**Also:** The `build_capability_profile` method (line 246) has a mutual exclusivity guard that skips `caddy_native` if Apache/Nginx/LiteSpeed/IIS is detected — this is correct, but the key itself is never defined in the matrix.

### 3. Features Showing `-` (Correctly)

Features with ONLY server-specific platforms (e.g., `Nginx`-only, `Apache`-only, `Caddy`-only) will correctly show `-` when the environment doesn't match. This is **by design** — you can't enforce an Nginx directive on Apache.

**However**, the current system has **no fallback suggestion** for these features. A more intelligent approach would be to:
- Show the platform name with a visual indicator that it's "Not Available" on the current server
- Or suggest a PHP-based alternative where possible

---

## Proposed Changes

### Component 1: PHP Environment Detector

#### [MODIFY] [class-vaptsecure-environment-detector.php](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/includes/class-vaptsecure-environment-detector.php)

Add the missing `caddy_native` entry to the `capability_matrix`:

```diff
 'php_functions' => [
   'detected_by' => ['php_sapi_detection:any'],
   'capabilities' => ['universal_fallback', 'application_level'],
   'requirements' => ['php_execution']
-]
+],
+'caddy_native' => [
+  'detected_by' => ['server_software_header:caddy', 'filesystem_probe:caddy'],
+  'capabilities' => ['high_performance', 'respond_block'],
+  'requirements' => ['caddyfile_writable']
+]
```

Also add `caddy` to the `detection_cascade` function availability tests:

```diff
 'function_availability' => [..., 'tests' => [
   'apache' => ['apache_get_modules'],
-  'litespeed' => ['litespeed_finish_request']
+  'litespeed' => ['litespeed_finish_request'],
+  'caddy' => []  // No PHP function test for Caddy; rely on filesystem/header
 ]]
```

---

### Component 2: JS `resolveEnforcer` — Intelligent Fallback

#### [MODIFY] [admin.js](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/js/admin.js)

Enhance `resolveEnforcer()` (~line 4348) to add a **universal PHP fallback** when the feature has no compatible server-level enforcer. This ensures features that only have `Nginx`, `Apache`, or `Caddy` enforcers still show a meaningful indicator rather than `-`.

```diff
 const resolveEnforcer = (feature) => {
   // ...existing filtering logic...

   const compatibleEnforcers = Array.from(availableEnforcers).filter(enf => {
     // ...existing filter...
   });

+  // Intelligent Fallback: If no compatible enforcers AND feature has
+  // server-specific-only platforms, add a "Not Available" indicator
+  // but keep PHP Functions as universal fallback if the feature supports it
+  if (compatibleEnforcers.length === 0 && availableEnforcers.size > 0) {
+    // Check if any available enforcer is a universal platform
+    const universalPlatforms = ['PHP Functions', 'wp-config.php', 'fail2ban', 'Server Cron'];
+    const hasUniversal = Array.from(availableEnforcers).some(e => universalPlatforms.includes(e));
+    if (hasUniversal) {
+      return Array.from(availableEnforcers).filter(e => universalPlatforms.includes(e));
+    }
+  }

   return compatibleEnforcers;
 };
```

---

### Component 3: Enforcer Column UI — Visual Server Indicator

#### [MODIFY] [admin.js](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/assets/js/admin.js)

Update the enforcer column rendering (~ line 5029) to show the **detected server** when a feature's enforcer is `-` (no compatible enforcer), so the admin knows WHY it's unavailable:

```diff
 } else if (col === 'enforcer') {
   const choices = resolveEnforcer(f);
   if (choices.length === 0) {
-    content = el('span', { style: { color: '#949494', fontStyle: 'italic' } }, '-');
+    // Show which platforms the feature needs vs what's available
+    const needed = Object.keys(f.platform_implementations || {});
+    const detectedServer = environmentProfile?.optimal_platform || 'unknown';
+    content = el(Tooltip, {
+      text: `Requires: ${needed.join(', ')}. Current server: ${detectedServer}`,
+    }, el('span', { style: { color: '#949494', fontStyle: 'italic' } }, 'N/A'));
```

---

## Verification Plan

### Browser-Based Verification
1. Navigate to `http://hermasnet.local/wp-admin/admin.php?page=vaptsecure-domain-admin`
2. Enable the "Enforcer" column if not already visible
3. Verify the following feature types show correct values:
   - **RISK-001** (`wp-config.php` only) → Shows `wp-config.php`
   - **RISK-002** (`.htaccess`, `Caddy`, `Cloudflare`, `IIS`) → Shows `.htaccess` on Apache
   - **RISK-004** (`PHP Functions` only) → Shows `PHP Functions`
   - **RISK-007** (`fail2ban` only) → Shows `fail2ban`
   - Server-specific features (Nginx-only, Caddy-only) → Shows `N/A` with tooltip

### Manual Verification
User should confirm:
1. Features count: All 125 features visible
2. No feature shows empty/broken enforcer (should show either a valid enforcer name or `N/A` with tooltip)
3. On an Apache server: `.htaccess` and `Apache` enforcers are visible; `Nginx`/`Caddy`-only features show `N/A`

---

## Revision History

| Timestamp | Change |
|-----------|--------|
| 20260322_@1620 | Initial analysis and plan created |
