<?php
/**
 * VAPTSECURE Config Cleaner
 *
 * Shared class for cleaning VAPT Secure configuration blocks from various files.
 * Extracts duplicated logic from Enforcer and License Manager classes.
 *
 * @package VAPT-Secure
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class VAPTSECURE_Config_Cleaner
 */
class VAPTSECURE_Config_Cleaner
{
    /**
     * Clean all configuration files of VAPT Secure blocks.
     *
     * Removes VAPT configuration blocks from:
     * - .htaccess
     * - wp-config.php
     * - vapt-functions.php
     * - nginx.conf
     * - web.config
     * - Caddyfile
     *
     * @return void
     */
    public static function clean_all()
    {
        // Clean .htaccess
        $htaccess = ABSPATH . '.htaccess';
        if (file_exists($htaccess) && is_writable($htaccess)) {
            $content = file_get_contents($htaccess);
            
            // Remove VAPT blocks (both single and multi-line)
            $content = preg_replace('/# BEGIN VAPT[^\n]*\n.*?# END VAPT[^\n]*/s', '', $content);
            $content = preg_replace('/# BEGIN VAPT-RISK[^\n]*\n.*?# END VAPT-RISK[^\n]*/s', '', $content);
            
            // Clean up extra newlines
            $content = preg_replace('/\n{3,}/', "\n\n", $content);
            
            file_put_contents($htaccess, $content);
        }
    
        // Clean wp-config.php
        $wp_config = ABSPATH . 'wp-config.php';
        if (file_exists($wp_config) && is_writable($wp_config)) {
            $content = file_get_contents($wp_config);
            
            // Remove VAPT blocks (both PHP comments and line comments)
            $content = preg_replace('/\/\/ BEGIN VAPT[^\n]*\n.*?\/\/ END VAPT[^\n]*/s', '', $content);
            $content = preg_replace('/\/\* BEGIN VAPT[^\n]*\*\/.*?\/\* END VAPT[^\n]*\*\//s', '', $content);
            
            // Clean up extra newlines
            $content = preg_replace('/\n{3,}/', "\n\n", $content);
            
            file_put_contents($wp_config, $content);
        }
    
        // Clean vapt-functions.php
        $vapt_func = VAPTSECURE_PATH . 'vapt-functions.php';
        if (file_exists($vapt_func) && is_writable($vapt_func)) {
            $content = "<?php\n\n/**\n * VAPT Secure Functions\n * License Expired - Functions Disabled\n */\n\nif (!defined('ABSPATH')) { exit; }\n\n";
            file_put_contents($vapt_func, $content);
        }
    
        // Clean nginx.conf if exists
        $nginx_conf = ABSPATH . 'nginx.conf';
        if (file_exists($nginx_conf) && is_writable($nginx_conf)) {
            $content = file_get_contents($nginx_conf);
            $content = preg_replace('/# BEGIN VAPT[^\n]*\n.*?# END VAPT[^\n]*/s', '', $content);
            file_put_contents($nginx_conf, $content);
        }
    
        // Clean web.config if exists (IIS)
        $web_config = ABSPATH . 'web.config';
        if (file_exists($web_config) && is_writable($web_config)) {
            $content = file_get_contents($web_config);
            $content = preg_replace('/<!-- BEGIN VAPT[^\n]*-->.*?<!-- END VAPT[^\n]*-->/s', '', $content);
            file_put_contents($web_config, $content);
        }
    
        // Clean Caddyfile if exists
        $caddyfile = ABSPATH . 'Caddyfile';
        if (file_exists($caddyfile) && is_writable($caddyfile)) {
            $content = file_get_contents($caddyfile);
            $content = preg_replace('/# BEGIN VAPT[^\n]*\n.*?# END VAPT[^\n]*/s', '', $content);
            file_put_contents($caddyfile, $content);
        }
    }
}