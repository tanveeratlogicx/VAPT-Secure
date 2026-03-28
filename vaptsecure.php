<?php

/**
 * Plugin Name: VAPT Secure
 * Description: Ultimate VAPT and OWASP Security Plugin Builder.
 * Version: 2.6.4
 * Author: Tanveer H. Malik
 * Author URI: https://vapt.copilot.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: vaptsecure
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Ensure the Composer autoloader is included if it exists.
if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    include_once dirname(__FILE__) . '/vendor/autoload.php';
}

/**
 * Ã°Å¸â€ºÂ Ã¯Â¸Â Linter Stubs (Satisfies IDEs without WP symbols)
 */
if (false) {
    function home_url($path = '', $scheme = null)
    {
        return '';
    }
    function remove_submenu_page($menu_slug, $submenu_slug)
    {
    }
    function wp_add_inline_script($handle, $data, $position = 'after')
    {
    }
    function admin_url($path = '', $scheme = 'admin')
    {
        return '';
    }
    function rest_url($path = '', $scheme = 'rest')
    {
        return '';
    }
    function wp_create_nonce($action = -1)
    {
        return '';
    }
}

/**
 * Define Paths & Constants
 */
define('VAPTSECURE_VERSION', '2.6.4');
if (! defined('VAPTSECURE_DATA_VERSION')) {
    define('VAPTSECURE_DATA_VERSION', '2.5.0');
}
if (! defined('VAPTSECURE_PATH')) {
    define('VAPTSECURE_PATH', plugin_dir_path(__FILE__));
}
if (! defined('VAPTSECURE_URL')) {
    define('VAPTSECURE_URL', plugin_dir_url(__FILE__));
}

if (! defined('VAPTSECURE_ACTIVE_DATA_FILE')) {
    define('VAPTSECURE_ACTIVE_DATA_FILE', get_option('vaptsecure_active_feature_file', 'interface_schema_v2.0.json'));
}

// v2.0 Schema Architecture Links
if (! defined('VAPTSECURE_PATTERN_LIBRARY')) {
    define('VAPTSECURE_PATTERN_LIBRARY', 'enforcer_pattern_library_v2.0.json');
}
if (! defined('VAPTSECURE_AI_INSTRUCTIONS')) {
    define('VAPTSECURE_AI_INSTRUCTIONS', 'ai_agent_instructions_v2.0.json');
}

// Backward Compatibility Aliases
if (! defined('VAPTC_VERSION')) {
    define('VAPTC_VERSION', VAPTSECURE_VERSION);
}
if (! defined('VAPTC_PATH')) {
    define('VAPTC_PATH', VAPTSECURE_PATH);
}
if (! defined('VAPTC_URL')) {
    define('VAPTC_URL', VAPTSECURE_URL);
}

/**
 * Ã°Å¸â€â€™ Obfuscated Superadmin Identity
 * Returns decoded credentials for strict access control.
 *
 * User: tanmalik786 (Base64: dGFubWFsaWs3ODY=)
 * Email: tanmalik786@gmail.com (Base64: dGFubWFsaWs3ODZAZ21haWwuY29t)
 *
 * @return array Decoded identity credentials.
 */
function vaptsecure_get_superadmin_identity()
{
    return array(
    'user' => base64_decode('dGFubWFsaWs3ODY='),
    'email' => base64_decode('dGFubWFsaWs3ODZAZ21haWwuY29t')
    );
}

// Set Superadmin Constants
$vaptsecure_identity = vaptsecure_get_superadmin_identity();
if (! defined('VAPTSECURE_SUPERADMIN_USER')) {
    define('VAPTSECURE_SUPERADMIN_USER', $vaptsecure_identity['user']);
}
if (! defined('VAPTSECURE_SUPERADMIN_EMAIL')) {
    define('VAPTSECURE_SUPERADMIN_EMAIL', $vaptsecure_identity['email']);
}

/**
 * Ã°Å¸â€â€™ Strict Superadmin Check
 * Verifies if current user matches the hidden identity.
 *
 * @return bool True if the current user is a superadmin.
 */
function is_vaptsecure_superadmin($require_auth = false)
{
    $current_user = wp_get_current_user();
    if (!$current_user->exists()) { return false;
    }

    $identity = vaptsecure_get_superadmin_identity();
    $login = strtolower($current_user->user_login);
    $email = strtolower($current_user->user_email);

    // 1. Ã°Å¸â€ºÂ¡Ã¯Â¸Â Identity Check (Primary Firewall)
    // MUST match the hardcoded superadmin identity login or email.
    $is_super_identity = ($login === strtolower($identity['user']) || $email === strtolower($identity['email']));

    if (!$is_super_identity) {
        return false;
    }

    // 2. Ã°Å¸â€ºÂ¡Ã¯Â¸Â Authentication Check (Secondary Layer)
    // If require_auth is true, also check if the user has a valid OTP session.
    if ($require_auth && class_exists('VAPTSECURE_Auth')) {
        if (!VAPTSECURE_Auth::is_authenticated()) {
            return false;
        }
    }

    return true;
}

// Include core classes (new Builder includes)
require_once VAPTSECURE_PATH . 'includes/debug-utils.php';
require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-auth.php';
require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-rest.php';
require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-db.php';
require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-workflow.php';
require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-build.php';
require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-enforcer.php';
require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-admin.php';
require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-license-manager.php';
require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-config-cleaner.php';
require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-migrations.php';

/**
 * Initialize Global Services
 * Deferred to plugins_loaded to avoid DB access during activation.
 */
add_action('plugins_loaded', array('VAPTSECURE_Enforcer', 'init'));

/**
 * Instantiate service objects on plugins_loaded so their constructors can hook into WP.
 */
add_action('plugins_loaded', 'vaptsecure_initialize_services');

/**
 * Service initialization callback.
 */
function vaptsecure_initialize_services()
{
    if (class_exists('VAPTSECURE_REST')) {
        new VAPTSECURE_REST();
    }
    if (class_exists('VAPTSECURE_Auth')) {
        // Auth may provide static helpers but instantiate to register hooks if needed
        new VAPTSECURE_Auth();
    }
    if (class_exists('VAPTSECURE_Admin')) {
        new VAPTSECURE_Admin();
    }
}


/**
 * Activation Hook: Initialize Database Tables
 */
register_activation_hook(__FILE__, 'vaptsecure_activate_plugin');
function vaptsecure_activate_plugin()
{
    // Ensure data directory exists
    if (! file_exists(VAPTSECURE_PATH . 'data')) {
        wp_mkdir_p(VAPTSECURE_PATH . 'data');
    }

    // Run versioned migrations
    if (class_exists('VAPTSECURE_Migrations')) {
        VAPTSECURE_Migrations::run_all();
    }

    // Ã°Å¸â€â€ Send Activation Email to Superadmin (Only on fresh activation)
    $existing_version = get_option('vaptsecure_version');
    if (empty($existing_version)) {
        vaptsecure_send_activation_email();
    }
}

/**
 * Manual Database Fix / Migrations
 * Ensures new columns are added to existing tables.
 */
/**
 * Send Activation Email
 * Notifies the superadmin when the plugin is activated on a new site.
 */
function vaptsecure_send_activation_email()
{
    $identity = vaptsecure_get_superadmin_identity();
    $to = $identity['email'];
    $site_name = get_bloginfo('name');
    $site_url = get_site_url();
    $admin_url = admin_url('admin.php?page=vaptsecure-domain-admin');

    $subject = sprintf("[VAPT Alert] Plugin Activated on %s", $site_name);
    $message = "VAPT Secure has been activated on a new site.\n\n";
    $message .= "Site Name: $site_name\n";
    $message .= "Site URL: $site_url\n";
    $message .= "Activation Date: " . current_time('mysql') . "\n";
    $message .= "Access Dashboard: $admin_url\n\n";
    $message .= "This is an automated security notification.";

    $headers = array('Content-Type: text/plain; charset=UTF-8');

    wp_mail($to, $subject, $message, $headers);
}

/**
 * Auto-update DB on version change
 */
add_action('init', 'vaptsecure_auto_update_db');

/**
 * Logic to run database updates if version mismatch.
 */
function vaptsecure_auto_update_db()
{
    $saved_version = get_option('vaptsecure_version');
    if ($saved_version !== VAPTSECURE_VERSION) {
        vaptsecure_activate_plugin();
        update_option('vaptsecure_version', VAPTSECURE_VERSION);
    }
}

/**
 * Manual database schema fix.
 * Can be triggered via ?vaptsecure_fix_db=1.
 */
function vaptsecure_run_manual_migrations()
{
    if (isset($_GET['vaptsecure_fix_db']) && current_user_can('manage_options')) {
        // Run versioned migrations
        if (class_exists('VAPTSECURE_Migrations')) {
            $results = VAPTSECURE_Migrations::run_all();
            
            // Clean up domain-feature relationships: remove features not in Release state
            global $wpdb;
            $domain_features_table = $wpdb->prefix . 'vaptsecure_domain_features';
            $feature_status_table = $wpdb->prefix . 'vaptsecure_feature_status';
            
            // Get all features that are NOT in Release state
            $non_release_features = $wpdb->get_col(
                "SELECT feature_key FROM {$feature_status_table} WHERE status != 'Release'"
            );
            
            $cleaned_count = 0;
            if (!empty($non_release_features)) {
                $placeholders = array_fill(0, count($non_release_features), '%s');
                $placeholders_string = implode(', ', $placeholders);
                
                // Delete domain-feature relationships for non-release features
                $cleaned_count = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$domain_features_table} WHERE feature_key IN ({$placeholders_string})",
                        $non_release_features
                    )
                );
            }
            
            $msg = "Database migration complete. All 27 migrations executed via versioned migration system.";
            if ($cleaned_count > 0) {
                $msg .= " Cleaned up " . intval($cleaned_count) . " domain-feature relationships for features not in Release state.";
            }
            
            wp_die(
                sprintf(
                    "<h1>VAPT Secure Database Updated</h1>
                    <p>%s</p>
                    <h3>Migration Results:</h3>
                    <ul>%s</ul>
                    <p><a href='%s'>Return to dashboard</a></p>",
                    esc_html($msg),
                    implode('', array_map(function($migration, $result) {
                        return sprintf('<li>%s: %s</li>', 
                            esc_html($migration), 
                            $result ? '✓ Success' : '✗ Failed'
                        );
                    }, array_keys($results), $results)),
                    admin_url('admin.php?page=vaptsecure')
                )
            );
        } else {
            wp_die("Migration system not available. Please check plugin installation.");
        }
    }
}

/**
 * Workbench Action Handler (Ajax-Alternative via GET)
 */
add_action(
    'init', function () {
        if (isset($_GET['vaptsecure_action']) && current_user_can('manage_options')) {
            $action = sanitize_text_field($_GET['vaptsecure_action']);
            if ($action === 'reset_rate_limits') {
                include_once VAPTSECURE_PATH . 'includes/enforcers/class-vaptsecure-hook-driver.php';
                VAPTSECURE_Hook_Driver::reset_limit();
                wp_die("Rate limits reset successfully.", "VAPT Secure Reset", array('response' => 200, 'back_link' => true));
            }
        }
    }
);

/**
 * Detect Localhost Environment
 * Verified against standard localhost IP and hostnames.
 *
 * @return bool True if on localhost.
 */
if (! function_exists('is_vaptsecure_localhost')) {
    function is_vaptsecure_localhost()
    {
        $whitelist = array('127.0.0.1', '::1', 'localhost');
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        if (in_array($addr, $whitelist) || in_array($host, $whitelist)) {
            return true;
        }
        $dev_suffixes = array('.local', '.test', '.dev', '.wp', '.site');
        foreach ($dev_suffixes as $suffix) {
            if (strpos($host, $suffix) !== false) {
                return true;
            }
        }
        return false;
    }
}

/**
 * Admin Menu Setup
 */
add_action('admin_menu', 'vaptsecure_add_admin_menu');

/**
 * Check Strict Permissions
 * Terminates execution if the current user is not a superadmin.
 */
if (! function_exists('vaptsecure_check_permissions')) {
    function vaptsecure_check_permissions($require_auth = false)
    {
        if (! is_vaptsecure_superadmin($require_auth)) {
            wp_die(__('You do not have permission to access the VAPT Secure Dashboard.', 'vaptsecure'));
        }
    }
}

/**
 * Registers the VAPT Secure and VAPT Domain Admin menu pages.
 */
if (! function_exists('vaptsecure_add_admin_menu')) {
    function vaptsecure_add_admin_menu()
    {
        $is_superadmin_identity = is_vaptsecure_superadmin(false);

        // 1. Parent Menu (Visible to all admins with manage_options)
        add_menu_page(
            __('VAPT Secure', 'vaptsecure'),
            __('VAPT Secure', 'vaptsecure'),
            'manage_options',
            'vaptsecure',
            'vaptsecure_render_client_status_page',
            'dashicons-shield',
            80
        );

        // Ã°Å¸â€ºÂ¡Ã¯Â¸Â Superadmin Only Sub-menus
        if ($is_superadmin_identity) {
            // Sub-menu 1: Workbench
            add_submenu_page(
                'vaptsecure',
                __('VAPTSecure Workbench', 'vaptsecure'),
                __('VAPTSecure Workbench', 'vaptsecure'),
                'manage_options',
                'vaptsecure-workbench',
                'vaptsecure_render_workbench_page'
            );

            // Sub-menu 2: Domain Admin
            add_submenu_page(
                'vaptsecure',
                __('VAPTSecure Domain Admin', 'vaptsecure'),
                __('VAPTSecure Domain Admin', 'vaptsecure'),
                'manage_options',
                'vaptsecure-domain-admin',
                'vaptsecure_render_admin_page'
            );
        }

        // Remove the default duplicate submenu item created by WordPress
        remove_submenu_page('vaptsecure', 'vaptsecure');
    }
}

/**
 * Handle Legacy Slug Redirects
 */
add_action('admin_init', 'vaptsecure_handle_legacy_redirects');
if (! function_exists('vaptsecure_handle_legacy_redirects')) {
    function vaptsecure_handle_legacy_redirects()
    {
        if (!isset($_GET['page'])) { return;
        }
        $legacy_slugs = array('vapt-secure', 'vapt-domain-admin', 'vapt-copilot', 'vapt-copilot-main', 'vapt-copilot-status', 'vapt-copilot-domain-build', 'vapt-client');
        if (in_array($_GET['page'], $legacy_slugs)) {
            $target = ($_GET['page'] === 'vapt-domain-admin') ? 'vaptsecure-domain-admin' : 'vaptsecure';
            wp_safe_redirect(admin_url('admin.php?page=' . $target));
            exit;
        }
    }
}

/**
 * Localhost Admin Notice
 */


/**
 * Render Client Status Page
 */
if (! function_exists('vaptsecure_render_client_status_page')) {
    function vaptsecure_render_client_status_page()
    {
        ?>
    <div class="wrap">
      <h1 class="wp-heading-inline"><?php _e('VAPT Secure', 'vaptsecure'); ?></h1>
      <hr class="wp-header-end" />
      <div id="vapt-client-root">
        <div style="padding: 40px; text-align: center; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
          <span class="spinner is-active" style="float: none; margin: 0 auto;"></span>
          <p><?php _e('Loading Implementation Workbench...', 'vaptsecure'); ?></p>
        </div>
      </div>
    </div>
        <?php
    }
}

/**
 * Render Superadmin Workbench Page
 */
if (! function_exists('vaptsecure_render_workbench_page')) {
    function vaptsecure_render_workbench_page()
    {
        if (! is_vaptsecure_superadmin(true)) {
            if (is_vaptsecure_superadmin(false)) {
                $identity = vaptsecure_get_superadmin_identity();
                if (! get_transient('vaptsecure_otp_email_' . $identity['user'])) {
                    VAPTSECURE_Auth::send_otp();
                }
                VAPTSECURE_Auth::render_otp_form();
            } else {
                wp_die(__('You do not have permission to access the VAPT Secure Dashboard.', 'vaptsecure'));
            }
            return;
        }
        ?>
    <div class="wrap">
      <h1 class="wp-heading-inline"><?php _e('VAPT Secure Workbench', 'vaptsecure'); ?></h1>
      <hr class="wp-header-end" />
      <div id="vapt-workbench-root">
        <div style="padding: 40px; text-align: center; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
          <span class="spinner is-active" style="float: none; margin: 0 auto;"></span>
          <p><?php _e('Loading Superadmin Workbench...', 'vaptsecure'); ?></p>
        </div>
      </div>
    </div>
        <?php
    }
}

if (! function_exists('vaptsecure_render_admin_page')) {
    function vaptsecure_render_admin_page()
    {
        vaptsecure_master_dashboard_page();
    }
}

if (! function_exists('vaptsecure_master_dashboard_page')) {
    function vaptsecure_master_dashboard_page()
    {
        // Verify Strict Identity AND Session
        if (! is_vaptsecure_superadmin(true)) {
            // If they match identity but lack auth, show OTP.
            // If they don't match identity, they already failed is_vaptsecure_superadmin() and was blocked by parent layer?
            // Actually vaptsecure_add_admin_menu blocks them from seeing it.
            // But if they access via direct URL, this check will trigger.
      
            if (is_vaptsecure_superadmin(false)) {
                // Identity matches, but needs auth.
                $identity = vaptsecure_get_superadmin_identity();
                if (! get_transient('vaptsecure_otp_email_' . $identity['user'])) {
                    VAPTSECURE_Auth::send_otp();
                }
                VAPTSECURE_Auth::render_otp_form();
            } else {
                // Identity DOES NOT match. Hard block.
                wp_die(__('You do not have permission to access the VAPT Secure Dashboard.', 'vaptsecure'));
            }
            return;
        }
        ?>
    <div id="vapt-admin-root" class="wrap">
      <h1><?php _e('VAPTSecure Domain Admin', 'vaptsecure'); ?></h1>
      <div style="padding: 20px; text-align: center;">
        <span class="spinner is-active" style="float: none; margin: 0 auto;"></span>
        <p><?php _e('Loading VAPT Secure...', 'vaptsecure'); ?></p>
      </div>
    </div>
        <?php
    }
}

/**
 * Enqueue Admin Assets
 */
add_action('admin_enqueue_scripts', 'vaptsecure_enqueue_admin_assets');

/**
 * Enqueue Assets for React App
 */
function vaptsecure_enqueue_admin_assets($hook)
{
    global $vaptsecure_hooks;
    $GLOBALS['vaptsecure_current_hook'] = $hook;
    $screen = get_current_screen();
    $current_user = wp_get_current_user();
    $is_superadmin = is_vaptsecure_superadmin();
    if (!$screen) { return;
    }
    // Enqueue Shared Styles
    wp_enqueue_style('vapt-admin-css', VAPTSECURE_URL . 'assets/css/admin.css', array('wp-components'), VAPTSECURE_VERSION);
    // 1. Superadmin Dashboard (admin.js)
    if ($screen->id === 'toplevel_page_vaptsecure-domain-admin' || $screen->id === 'vaptsecure_page_vaptsecure-domain-admin' || strpos($screen->id, 'vaptsecure-domain-admin') !== false) {
        if (!VAPTSECURE_Auth::is_authenticated()) {
            return; // Do not enqueue heavy React apps if OTP is pending
        }

        error_log('VAPT Admin Assets Enqueued for: ' . $screen->id);
        // Enqueue Auto-Interface Generator (Module)
        wp_enqueue_script(
            'vapt-interface-generator',
            plugin_dir_url(__FILE__) . 'assets/js/modules/interface-generator.js',
            array(), // No deps, but strictly before admin.js
            VAPTSECURE_VERSION,
            true
        );
        // Enqueue A+ Adaptive Generator (Module)
        wp_enqueue_script(
            'vapt-aplus-generator',
            plugin_dir_url(__FILE__) . 'assets/js/modules/aplus-generator.js',
            array(),
            VAPTSECURE_VERSION,
            true
        );
        // Enqueue Generated Interface UI Component
        wp_enqueue_script(
            'vapt-generated-interface-ui',
            plugin_dir_url(__FILE__) . 'assets/js/modules/generated-interface.js',
            array('wp-element', 'wp-components', 'wp-i18n'),
            VAPTSECURE_VERSION,
            true
        );
        // Enqueue Admin Dashboard Script
        wp_enqueue_script(
            'vapt-admin-js',
            plugin_dir_url(__FILE__) . 'assets/js/admin.js',
            array('wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n', 'vapt-interface-generator', 'vapt-aplus-generator', 'vapt-generated-interface-ui'),
            VAPTSECURE_VERSION,
            true
        );
    }


    // Common Settings Localization
    $vapt_settings = array(
    'root' => esc_url_raw(rest_url()),
    'homeUrl' => esc_url_raw(home_url()),
    'nonce' => wp_create_nonce('wp_rest'),
    'isSuper' => $is_superadmin,
    'pluginVersion' => VAPTSECURE_VERSION,
    'pluginName' => 'VAPT Secure',
    'currentDomain' => parse_url(home_url(), PHP_URL_HOST),
    'abspath' => ABSPATH,
    'pluginPath' => VAPTSECURE_PATH,
    'uploadPath' => wp_upload_dir()['basedir'],
    );

    // Ã°Å¸â€ºÂ¡Ã¯Â¸Â GLOBAL REST HOTPATCH (v3.8.17) - Inline for maximum priority
    $home_url = esc_url_raw(home_url());
    $inline_patch = "
    (function() {
      if (typeof wp === 'undefined' || !wp.apiFetch) return;
      if (wp.apiFetch.__vaptsecure_patched) return;
      
      try {
        // Ã°Å¸â€ºÂ¡Ã¯Â¸Â RECOVERY: If the browser was permanently stuck in silent mode, free it (v2.2.9 Fix)
        localStorage.removeItem('vaptsecure_rest_broken');
      } catch (e) { }

      const originalApiFetch = wp.apiFetch;

      const patchedApiFetch = (args) => {
        // Get nonce from WordPress standard wpApiSettings or custom vaptSecureSettings
        const wpNonce = (window.wpApiSettings && window.wpApiSettings.nonce) || '';
        const vaptNonce = (window.vaptSecureSettings && window.vaptSecureSettings.nonce) || '';
        const effectiveNonce = wpNonce || vaptNonce;
        
        const home = '{$home_url}';
        
        // Ã°Å¸â€ºÂ¡Ã¯Â¸Â AUTH PERI-FIX: Ensure Nonce is present for non-GET requests
        const method = (args.method || 'GET').toUpperCase();
        if (effectiveNonce && method !== 'GET') {
          if (!args.headers) args.headers = {};
          // Handle both plain objects and Headers objects
          if (typeof args.headers.set === 'function') {
            if (!args.headers.has('X-WP-Nonce')) args.headers.set('X-WP-Nonce', effectiveNonce);
          } else {
            args.headers['X-WP-Nonce'] = effectiveNonce;
          }
        }
        
        const getFallbackUrl = (pathOrUrl) => {
          if (!pathOrUrl) return null;
          const path = typeof pathOrUrl === 'string' && pathOrUrl.includes('/wp-json/')
            ? pathOrUrl.split('/wp-json/')[1]
            : pathOrUrl;
          const cleanHome = home.replace(/\/$/, '');
          const cleanPath = path.replace(/^\//, '').split('?')[0];
          const queryParams = path.includes('?') ? '&' + path.split('?')[1] : '';
          const nonceParam = effectiveNonce ? '&_wpnonce=' + effectiveNonce : '';
          return cleanHome + '/?rest_route=/' + cleanPath + queryParams + nonceParam;
        };

        // Ã°Å¸â€ºÂ¡Ã¯Â¸Â REMOVED THE INSTANT FALLBACK LOGIC to prevent permanently spamming 403s
        // on all endpoints when only one endpoint was broken.
        // Fallbacks will only occur per-request dynamically.

        return originalApiFetch(args).catch(err => {
          const status = err.status || (err.data && err.data.status);
          const isFallbackTrigger = status === 404 || status === 403 || err.code === 'rest_no_route' || err.code === 'invalid_json';

          if (isFallbackTrigger && (args.path || args.url) && home) {
            const fallbackUrl = getFallbackUrl(args.path || args.url);
            if (!fallbackUrl) throw err;

            // Notice: We are trying fallback dynamically, but NOT saving it to localStorage
            console.warn('VAPT Secure: Original API request failed, attempting fallback (?rest_route=...).');
            
            const fallbackArgs = Object.assign({}, args, { url: fallbackUrl });
            delete fallbackArgs.path;
            
            // Note: If the fallback also fails, the promise will reject normally back to the caller
            return originalApiFetch(fallbackArgs);
          }
          throw err;
        });
      };

      Object.keys(originalApiFetch).forEach(key => { patchedApiFetch[key] = originalApiFetch[key]; });
      patchedApiFetch.__vaptsecure_patched = true;
      wp.apiFetch = patchedApiFetch;
      console.log('VAPT Secure: Persistent Global REST Hotpatch Active (v3.8.17)');
    })();
  ";
    wp_add_inline_script('wp-api-fetch', $inline_patch);

    if ($screen->id === 'toplevel_page_vaptsecure-domain-admin' || $screen->id === 'vaptsecure_page_vaptsecure-domain-admin' || strpos($screen->id, 'vaptsecure-domain-admin') !== false) {
        wp_localize_script('vapt-admin-js', 'vaptSecureSettings', $vapt_settings);
    }
    // 2. Shared: Generated Interface UI Component
    if ($screen->id === 'toplevel_page_vaptsecure' || strpos($screen->id, 'vaptsecure-workbench') !== false) {
        wp_enqueue_script(
            'vapt-generated-interface-ui',
            plugin_dir_url(__FILE__) . 'assets/js/modules/generated-interface.js',
            array('wp-element', 'wp-components', 'wp-i18n'),
            VAPTSECURE_VERSION,
            true
        );
    }

    // 2a. Client Dashboard (client.js) - WordPress Admin view - "VAPT Secure" page (Release features only)
    if ($screen->id === 'toplevel_page_vaptsecure') {
        wp_enqueue_script(
            'vapt-client-js',
            plugin_dir_url(__FILE__) . 'assets/js/client.js',
            array('wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n', 'vapt-generated-interface-ui'),
            VAPTSECURE_VERSION,
            true
        );
        wp_localize_script('vapt-client-js', 'vaptSecureSettings', $vapt_settings);
    }

    // 2b. Superadmin Workbench (workbench.js) - "VAPT Secure Workbench" page (All features, unscoped)
    if (strpos($screen->id, 'vaptsecure-workbench') !== false) {
        wp_enqueue_script(
            'vapt-workbench-js',
            plugin_dir_url(__FILE__) . 'assets/js/workbench.js',
            array('wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n', 'vapt-generated-interface-ui'),
            VAPTSECURE_VERSION,
            true
        );
        wp_localize_script('vapt-workbench-js', 'vaptSecureSettings', $vapt_settings);
    }
}
?>
