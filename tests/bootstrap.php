<?php
/**
 * PHPUnit Bootstrap File
 *
 * Loads WordPress function stubs for unit testing without full WordPress environment.
 */

// Define WordPress constants
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

// Mock WordPress classes
if (!class_exists('WP_Error')) {
    class WP_Error
    {
        private $errors = [];
        private $error_data = [];

        public function __construct($code = '', $message = '', $data = '')
        {
            if (empty($code)) {
                return;
            }
            $this->errors[$code][] = $message;
            if (!empty($data)) {
                $this->error_data[$code] = $data;
            }
        }

        public function get_error_code()
        {
            $codes = array_keys($this->errors);
            return empty($codes) ? '' : $codes[0];
        }

        public function get_error_message($code = '')
        {
            if (empty($code)) {
                $code = $this->get_error_code();
            }
            return isset($this->errors[$code]) ? $this->errors[$code][0] : '';
        }

        public function get_error_codes()
        {
            return array_keys($this->errors);
        }

        public function get_error_messages($code = '')
        {
            if (empty($code)) {
                $code = $this->get_error_code();
            }
            return isset($this->errors[$code]) ? $this->errors[$code] : [];
        }
    }
}

// Mock WordPress functions
if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        return $text;
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default')
    {
        echo $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str)
    {
        return trim(strip_tags($str));
    }
}

if (!function_exists('sanitize_title')) {
    function sanitize_title($title, $fallback_title = '', $context = 'save')
    {
        $title = strip_tags($title);
        $title = preg_replace('/[^\w\s-]/', '', $title);
        $title = preg_replace('/\s+/', '-', $title);
        return strtolower(trim($title, '-'));
    }
}

if (!function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($special_chars) {
            $chars .= '!@#$%^&*()';
        }
        if ($extra_special_chars) {
            $chars .= '-_ []{}<>~`+=,.;:/?|';
        }
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= substr($chars, wp_rand(0, strlen($chars) - 1), 1);
        }
        return $password;
    }
}

if (!function_exists('wp_rand')) {
    function wp_rand($min = 0, $max = 0)
    {
        if ($max === 0) {
            $max = getrandmax();
        }
        return rand($min, $max);
    }
}

if (!function_exists('get_temp_dir')) {
    function get_temp_dir()
    {
        return sys_get_temp_dir() . '/';
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0)
    {
        if ($type === 'mysql') {
            return date('Y-m-d H:i:s');
        }
        return time();
    }
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($target)
    {
        return @mkdir($target, 0755, true);
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id()
    {
        return 1;
    }
}

if (!function_exists('get_transient')) {
    function get_transient($transient)
    {
        return false;
    }
}

if (!function_exists('set_transient')) {
    function set_transient($transient, $value, $expiration = 0)
    {
        return true;
    }
}

// Load the classes to test
require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-workflow.php';
require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-enforcer.php';
require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-build.php';
