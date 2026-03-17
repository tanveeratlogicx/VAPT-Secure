<?php

/**
 * VAPT Secure: Centralized PHP Protections
 */

if (!defined('ABSPATH')) exit;

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
// END VAPT SECURITY RULES
