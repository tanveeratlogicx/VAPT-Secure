<?php

/**
 * REST API Router for VAPT Secure - Delegates to specialized controllers
 */

if (! defined('ABSPATH')) {
    exit;
}

class VAPTSECURE_REST
{
    private $controllers = array();

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        // Load and register all specialized controllers
        $this->load_controllers();
        
        foreach ($this->controllers as $controller) {
            if (method_exists($controller, 'register_routes')) {
                $controller->register_routes();
            }
        }

        // Register ping endpoint directly in router
        register_rest_route(
            'vaptsecure/v1', '/ping', array(
            'methods'  => 'GET',
            'callback' => function () {
                return new WP_REST_Response(['pong' => true], 200);
            },
            'permission_callback' => '__return_true',
            )
        );
    }

    private function load_controllers()
    {
        $controllers_dir = VAPTSECURE_PATH . 'includes/rest/';
        
        $controller_files = array(
            'class-vaptsecure-rest-features.php',
            'class-vaptsecure-rest-domains.php',
            'class-vaptsecure-rest-builds.php',
            'class-vaptsecure-rest-data-files.php',
            'class-vaptsecure-rest-security.php',
            'class-vaptsecure-rest-license.php',
            'class-vaptsecure-rest-settings.php'
        );

        foreach ($controller_files as $file) {
            $file_path = $controllers_dir . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
                
                $class_name = $this->get_class_name_from_file($file);
                if (class_exists($class_name)) {
                    $this->controllers[] = new $class_name();
                }
            }
        }
    }

    private function get_class_name_from_file($filename)
    {
        $name = str_replace('.php', '', $filename);
        $name = str_replace('class-', '', $name);
        $name = str_replace('-', '_', $name);
        return strtoupper($name);
    }

    // Keep backward compatibility methods for any direct calls
    public function check_permission()
    {
        $is_super = is_vaptsecure_superadmin();
        if (!$is_super) {
            $uid = get_current_user_id();
            $user = get_userdata($uid);
            $login = $user ? $user->user_login : 'unknown';
            error_log("VAPTSECURE_REST: check_permission FAILED for user ID $uid ($login). Superadmin status required.");
        }
        return $is_super;
    }

    public function check_read_permission()
    {
        $is_super = is_vaptsecure_superadmin();
        $can_manage = current_user_can('manage_options');
        $uid = get_current_user_id();
        $user = get_userdata($uid);
        $login = $user ? $user->user_login : 'unknown';

        error_log("VAPTSECURE_REST: check_read_permission - User ID: $uid ($login), is_super: " . ($is_super ? 'true' : 'false') . ", can_manage: " . ($can_manage ? 'true' : 'false'));

        if (!$is_super && !$can_manage) {
            error_log("VAPTSECURE_REST: check_read_permission FAILED for user ID $uid ($login). 'manage_options' capability required.");
        }
        return $is_super || $can_manage;
    }
}