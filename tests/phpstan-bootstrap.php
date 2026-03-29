<?php
/**
 * PHPStan Bootstrap File
 *
 * Provides stubs for WordPress functions that PHPStan needs to know about.
 */

// Define WordPress constants for static analysis
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

if (!defined('VAPTSECURE_PATH')) {
    define('VAPTSECURE_PATH', ABSPATH);
}

if (!defined('VAPTSECURE_URL')) {
    define('VAPTSECURE_URL', 'http://example.com/wp-content/plugins/VAPT-Secure/');
}

if (!defined('VAPTSECURE_VERSION')) {
    define('VAPTSECURE_VERSION', '1.0.0');
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

// Function stubs for PHPStan
function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {}
function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {}
function do_action($tag, ...$args) {}
function apply_filters($tag, $value, ...$args) {}
function remove_action($tag, $function_to_remove, $priority = 10) {}
function remove_filter($tag, $function_to_remove, $priority = 10) {}
function register_activation_hook($file, $function) {}
function register_deactivation_hook($file, $function) {}
function __($text, $domain = 'default') { return $text; }
function _e($text, $domain = 'default') { echo $text; }
function esc_html($text) { return $text; }
function esc_attr($text) { return $text; }
function esc_url($url, $protocols = null) { return $url; }
function esc_sql($data) { return $data; }
function sanitize_text_field($str) { return $str; }
function sanitize_title($title, $fallback_title = '', $context = 'save') { return $title; }
function sanitize_key($key) { return $key; }
function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false) { return str_repeat('a', $length); }
function wp_rand($min = 0, $max = 0) { return rand($min, $max); }
function get_temp_dir() { return sys_get_temp_dir() . '/'; }
function current_time($type, $gmt = 0) { return date('Y-m-d H:i:s'); }
function wp_mkdir_p($target) { return true; }
function get_current_user_id() { return 1; }
function get_transient($transient) { return false; }
function set_transient($transient, $value, $expiration = 0) { return true; }
function delete_transient($transient) { return true; }
function wp_upload_dir() { return ['basedir' => '/tmp', 'baseurl' => 'http://example.com/wp-content/uploads']; }
function get_option($option, $default = false) { return $default; }
function update_option($option, $value, $autoload = null) { return true; }
function wp_die($message = '', $title = '', $args = []) { exit; }
function wp_parse_args($args, $defaults = '') { return array_merge($defaults, $args); }
function is_wp_error($thing) { return $thing instanceof WP_Error; }
function trailingslashit($string) { return rtrim($string, '/') . '/'; }
function untrailingslashit($string) { return rtrim($string, '/'); }
function wp_json_encode($data, $options = 0, $depth = 512) { return json_encode($data, $options, $depth); }
function file_get_contents($filename, $use_include_path = false, $context = null, $offset = 0, $length = null) { return ''; }

// Class stubs
class WP_Error {
    private $errors = [];
    private $error_data = [];
    public function __construct($code = '', $message = '', $data = '') {}
    public function get_error_code() { return ''; }
    public function get_error_message($code = '') { return ''; }
    public function get_error_codes() { return []; }
    public function get_error_messages($code = '') { return []; }
    public function add($code, $message, $data = '') {}
    public function remove($code) {}
}

class wpdb {
    public $prefix = 'wp_';
    public $last_error = '';
    public function get_row($query, $output = OBJECT, $y = 0) { return null; }
    public function get_results($query, $output = OBJECT) { return []; }
    public function get_var($query, $x = 0, $y = 0) { return null; }
    public function get_col($query, $x = 0) { return []; }
    public function prepare($query, ...$args) { return $query; }
    public function insert($table, $data, $format = null) { return 1; }
    public function update($table, $data, $where, $format = null, $where_format = null) { return 1; }
    public function delete($table, $where, $where_format = null) { return 1; }
    public function query($query) { return 1; }
}
