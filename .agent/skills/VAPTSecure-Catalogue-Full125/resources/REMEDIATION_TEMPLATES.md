# Remediation Templates

Copy-paste remediation blocks for common finding categories.
Customise the `[PLACEHOLDERS]` before including in a report.

---

## SQL Injection

1. Replace all raw `$wpdb->query()` / `$wpdb->get_results()` calls with
   `$wpdb->prepare()` using parameterised placeholders (`%s`, `%d`).
2. Validate and sanitise all user-supplied input with `sanitize_text_field()`,
   `absint()`, or `esc_sql()` as appropriate.
3. Enable a WAF rule (e.g., ModSecurity CRS rule 942100) to block common
   SQL metacharacters in form fields.
4. Rotate the WordPress database user password and restrict its privileges to
   only `SELECT`, `INSERT`, `UPDATE`, `DELETE` on the wp_ tables.
5. Review and update to the latest WordPress core and affected plugins.

---

## Cross-Site Scripting (XSS)

1. Escape all output using `esc_html()`, `esc_attr()`, `esc_url()`, or
   `wp_kses_post()` depending on context.
2. Implement a strict Content Security Policy (CSP) header:
   `Content-Security-Policy: default-src 'self'; script-src 'self'`
3. Enable the `X-XSS-Protection: 1; mode=block` header as defence-in-depth.
4. For Stored XSS, audit all database fields that store user-supplied HTML
   and re-sanitise existing data with `wp_kses_post()`.
5. Add automated XSS scanning to the CI/CD pipeline (e.g., OWASP ZAP).

---

## File Upload Vulnerabilities

1. Restrict allowed MIME types in `wp_check_filetype()` to an explicit
   allowlist (e.g., image/jpeg, image/png, application/pdf).
2. Rename uploaded files to a random UUID on server side — never trust the
   client-supplied filename.
3. Store uploaded files outside the web root or configure the uploads
   directory with `Options -ExecCGI` and `php_flag engine off` in `.htaccess`.
4. Scan uploaded files with an anti-malware library (e.g., ClamAV) before
   making them accessible.
5. Set `X-Content-Type-Options: nosniff` to prevent MIME sniffing.

---

## Broken Access Control

1. Add `current_user_can('[CAPABILITY]')` checks at the top of every
   AJAX handler and REST API endpoint.
2. Register all REST routes with a `permission_callback` that validates
   the user's role.
3. Use WordPress nonces (`wp_nonce_field()` / `check_ajax_referer()`) on
   all state-changing requests.
4. Audit the `user_role` and `capabilities` tables for unexpected privilege
   grants introduced by plugins.
5. Enable an authorisation audit log plugin to track privilege changes.

---

## Security Misconfiguration

1. Set `WP_DEBUG` to `false` and `WP_DEBUG_LOG` to `false` in `wp-config.php`.
2. Remove all backup files from the web root (`wp-config.php.bak`, `*.sql`,
   `.env`).
3. Rename the default admin username from `admin` to a non-guessable value.
4. Disable XML-RPC if not required: add `add_filter('xmlrpc_enabled', '__return_false');`
5. Apply least-privilege file permissions: `wp-config.php` → 600,
   `wp-content/` → 755, uploaded files → 644.

---

## Outdated Components

1. Update WordPress core to the latest stable release via Dashboard → Updates.
2. Update all plugins and themes; remove any that are no longer maintained.
3. Enable automatic background updates for minor WordPress releases:
   add `define('WP_AUTO_UPDATE_CORE', 'minor');` to `wp-config.php`.
4. Subscribe to the [WordPress Security Team advisories](https://wordpress.org/news/category/security/)
   and set calendar reminders for quarterly plugin audits.
5. Replace any plugin with a known unpatched CVE with a maintained alternative.

---

## Authentication Failures

1. Enforce a minimum password length of 12 characters and complexity
   requirements via a plugin such as Password Policy Manager.
2. Enable MFA for all administrator and editor accounts using a TOTP plugin
   (e.g., WP 2FA).
3. Invalidate all session tokens on password change and on logout
   (`wp_destroy_all_sessions()`).
4. Implement account lockout after 5 failed login attempts (e.g., Limit
   Login Attempts Reloaded).
5. Disable user enumeration: block requests to `/?author=[n]` that return
   a 301 redirect exposing the username.

---

## SSRF

1. Whitelist allowed URL schemes and destinations in any plugin that
   performs server-side HTTP requests.
2. Block requests to private IP ranges (10.0.0.0/8, 172.16.0.0/12,
   192.168.0.0/16) and the cloud metadata endpoint (169.254.169.254).
3. Disable WordPress pingback/trackback if not required.
4. Use a dedicated outbound proxy that enforces the allowlist at the
   network layer.
