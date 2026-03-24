<?php

/**
 * VAPT Secure: Centralized PHP Protections
 */

if (!defined('ABSPATH')) { exit;
}

// BEGIN VAPT SECURITY RULES
// BEGIN VAPT RISK-004

add_action('login_init', 'vapt_rate_limit_password_reset');
function vapt_rate_limit_password_reset() {
    if ($_POST['user_login'] ?? false) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $transient = 'pwd_reset_' . md5($ip);
        if (get_transient($transient)) {
            wp_die('Too many attempts. Please try again later.');
        }
        set_transient($transient, true, 300); // 5 minutes
    }
}

// END VAPT RISK-004

// BEGIN VAPT RISK-006

add_filter('rest_authentication_errors', 'vapt_restrict_rest_api');
function vapt_restrict_rest_api($result) {
    if (!empty($result)) return $result;
    if (!is_user_logged_in()) {
        return new WP_Error('rest_not_logged_in', 'Authentication required.', array('status' => 401));
    }
    return $result;
}

// END VAPT RISK-006

// BEGIN VAPT RISK-008

add_filter('login_errors', 'vapt_hide_login_errors');
function vapt_hide_login_errors() {
    return 'Invalid credentials. Please try again.';
}

// END VAPT RISK-008

// BEGIN VAPT RISK-009

add_action('wp_enqueue_scripts', 'vapt_add_recaptcha_v3');
function vapt_add_recaptcha_v3() {
    wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=YOUR_SITE_KEY');
}

// END VAPT RISK-009
// END VAPT SECURITY RULES
