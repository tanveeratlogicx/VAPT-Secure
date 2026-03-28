<?php

/**
 * Settings REST Controller for VAPT Secure
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once VAPTSECURE_PATH . 'includes/rest/class-vaptsecure-rest-base.php';

class VAPTSECURE_REST_Settings extends VAPTSECURE_REST_Base
{
    public function register_routes()
    {
        register_rest_route(
            'vaptsecure/v1', '/settings/enforcement', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_global_enforcement'),
            'permission_callback' => array($this, 'check_read_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/settings/enforcement', array(
            'methods'  => 'POST',
            'callback' => array($this, 'update_global_enforcement'),
            'permission_callback' => array($this, 'check_read_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/upload-media', array(
            'methods'  => 'POST',
            'callback' => array($this, 'upload_media'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/assignees', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_assignees'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vaptsecure/v1', '/features/assign', array(
            'methods'  => 'POST',
            'callback' => array($this, 'update_assignment'),
            'permission_callback' => array($this, 'check_permission'),
            )
        );
    }

    public function get_global_enforcement($request)
    {
        $enforcement = get_option('vaptsecure_global_enforcement', array());

        $default_enforcement = array(
            'enabled' => true,
            'strict_mode' => false,
            'auto_update' => true,
            'log_level' => 'warning',
            'notify_admins' => true,
            'notify_email' => get_option('admin_email'),
            'block_malicious' => true,
            'rate_limit' => array(
                'enabled' => true,
                'requests_per_minute' => 100,
                'block_duration' => 300
            ),
            'scan_schedule' => array(
                'enabled' => true,
                'frequency' => 'daily',
                'time' => '02:00'
            )
        );

        $merged = wp_parse_args($enforcement, $default_enforcement);

        $stats = array(
            'domains_count' => $this->get_domains_count(),
            'features_count' => $this->get_features_count(),
            'active_enforcers' => $this->get_active_enforcers(),
            'last_scan' => get_option('vaptsecure_last_scan', null),
            'total_blocks' => get_option('vaptsecure_total_blocks', 0)
        );

        return $this->success_response(array(
            'enforcement' => $merged,
            'stats' => $stats,
            'environment' => $this->get_current_environment_profile()
        ));
    }

    public function update_global_enforcement($request)
    {
        $enforcement = $request->get_param('enforcement');
        
        if (!is_array($enforcement)) {
            return $this->error_response('enforcement must be an array', 'invalid_parameter', 400);
        }

        $sanitized = $this->sanitize_enforcement_settings($enforcement);
        update_option('vaptsecure_global_enforcement', $sanitized);

        $this->log_security_event('enforcement_updated', array(
            'changes' => array_keys($sanitized)
        ));

        return $this->success_response(array(
            'enforcement' => $sanitized,
            'updated' => true
        ), 'Enforcement settings updated successfully');
    }

    public function upload_media($request)
    {
        $files = $request->get_file_params();
        
        if (empty($files) || !isset($files['file'])) {
            return $this->error_response('No file uploaded', 'no_file', 400);
        }

        $file = $files['file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->error_response('File upload error: ' . $file['error'], 'upload_error', 400);
        }

        $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'json', 'xml');
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            return $this->error_response(
                'File type not allowed. Allowed types: ' . implode(', ', $allowed_types),
                'invalid_file_type',
                400
            );
        }

        $max_size = 10 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            return $this->error_response('File too large. Maximum size is 10MB', 'file_too_large', 400);
        }

        $upload_dir = wp_upload_dir();
        $vaptsecure_dir = $upload_dir['basedir'] . '/vaptsecure';
        
        if (!file_exists($vaptsecure_dir)) {
            wp_mkdir_p($vaptsecure_dir);
        }

        $filename = sanitize_file_name($file['name']);
        $target_path = $vaptsecure_dir . '/' . $filename;

        $counter = 1;
        while (file_exists($target_path)) {
            $filename = pathinfo($file['name'], PATHINFO_FILENAME) . '-' . $counter . '.' . $file_ext;
            $target_path = $vaptsecure_dir . '/' . $filename;
            $counter++;
        }

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $url = $upload_dir['baseurl'] . '/vaptsecure/' . $filename;

            $this->log_security_event('media_uploaded', array(
                'filename' => $filename,
                'type' => $file_ext,
                'size' => $file['size'],
                'url' => $url
            ));

            return $this->success_response(array(
                'filename' => $filename,
                'url' => $url,
                'path' => $target_path,
                'size' => $file['size'],
                'type' => $file_ext
            ), 'File uploaded successfully');
        }

        return $this->error_response('Failed to move uploaded file', 'file_move_error', 500);
    }

    public function get_assignees($request)
    {
        global $wpdb;

        $features_table = $wpdb->prefix . 'vaptsecure_feature_status';
        
        $assignees = $wpdb->get_col(
            "SELECT DISTINCT assigned_to FROM $features_table WHERE assigned_to IS NOT NULL AND assigned_to != ''"
        );

        $users = array();
        foreach ($assignees as $assignee) {
            if (is_numeric($assignee)) {
                $user = get_userdata($assignee);
                if ($user) {
                    $users[] = array(
                        'id' => $user->ID,
                        'username' => $user->user_login,
                        'email' => $user->user_email,
                        'display_name' => $user->display_name
                    );
                }
            } else {
                $users[] = array(
                    'id' => null,
                    'username' => $assignee,
                    'email' => '',
                    'display_name' => $assignee
                );
            }
        }

        $all_users = get_users(array(
            'role__in' => array('administrator', 'editor', 'author'),
            'fields' => array('ID', 'user_login', 'user_email', 'display_name')
        ));

        $available_users = array();
        foreach ($all_users as $user) {
            $available_users[] = array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name
            );
        }

        return $this->success_response(array(
            'current_assignees' => $users,
            'available_users' => $available_users
        ));
    }

    public function update_assignment($request)
    {
        $feature_key = $request->get_param('feature_key');
        $assigned_to = $request->get_param('assigned_to');

        if (empty($feature_key)) {
            return $this->error_response('feature_key is required', 'missing_parameter', 400);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'vaptsecure_feature_status';

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE feature_key = %s",
            $feature_key
        ));

        if (!$existing) {
            $wpdb->insert($table_name, array(
                'feature_key' => $feature_key,
                'assigned_to' => $assigned_to,
                'status' => 'draft',
                'implemented_at' => null
            ));
        } else {
            $wpdb->update($table_name, 
                array('assigned_to' => $assigned_to),
                array('feature_key' => $feature_key)
            );
        }

        $history_table = $wpdb->prefix . 'vaptsecure_feature_history';
        $wpdb->insert($history_table, array(
            'feature_key' => $feature_key,
            'event_type' => 'assignment_changed',
            'old_value' => $existing ? $existing->assigned_to : null,
            'new_value' => $assigned_to,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ));

        $this->log_security_event('feature_assignment_updated', array(
            'feature_key' => $feature_key,
            'assigned_to' => $assigned_to,
            'previous_assigned_to' => $existing ? $existing->assigned_to : null
        ));

        return $this->success_response(array(
            'feature_key' => $feature_key,
            'assigned_to' => $assigned_to,
            'updated' => true
        ), 'Assignment updated successfully');
    }

    private function sanitize_enforcement_settings($settings)
    {
        $sanitized = array();

        if (isset($settings['enabled'])) {
            $sanitized['enabled'] = (bool) $settings['enabled'];
        }

        if (isset($settings['strict_mode'])) {
            $sanitized['strict_mode'] = (bool) $settings['strict_mode'];
        }

        if (isset($settings['auto_update'])) {
            $sanitized['auto_update'] = (bool) $settings['auto_update'];
        }

        if (isset($settings['log_level'])) {
            $allowed_levels = array('debug', 'info', 'warning', 'error');
            $sanitized['log_level'] = in_array($settings['log_level'], $allowed_levels) ? 
                $settings['log_level'] : 'warning';
        }

        if (isset($settings['notify_admins'])) {
            $sanitized['notify_admins'] = (bool) $settings['notify_admins'];
        }

        if (isset($settings['notify_email'])) {
            $sanitized['notify_email'] = sanitize_email($settings['notify_email']);
        }

        if (isset($settings['block_malicious'])) {
            $sanitized['block_malicious'] = (bool) $settings['block_malicious'];
        }

        if (isset($settings['rate_limit']) && is_array($settings['rate_limit'])) {
            $rate_limit = $settings['rate_limit'];
            $sanitized['rate_limit'] = array(
                'enabled' => isset($rate_limit['enabled']) ? (bool) $rate_limit['enabled'] : true,
                'requests_per_minute' => isset($rate_limit['requests_per_minute']) ? 
                    max(1, (int) $rate_limit['requests_per_minute']) : 100,
                'block_duration' => isset($rate_limit['block_duration']) ? 
                    max(60, (int) $rate_limit['block_duration']) : 300
            );
        }

        if (isset($settings['scan_schedule']) && is_array($settings['scan_schedule'])) {
            $schedule = $settings['scan_schedule'];
            $sanitized['scan_schedule'] = array(
                'enabled' => isset($schedule['enabled']) ? (bool) $schedule['enabled'] : true,
                'frequency' => isset($schedule['frequency']) ? 
                    sanitize_text_field($schedule['frequency']) : 'daily',
                'time' => isset($schedule['time']) ? 
                    $this->validate_time($schedule['time']) : '02:00'
            );
        }

        return $sanitized;
    }

    private function validate_time($time)
    {
        if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            return $time;
        }
        return '02:00';
    }

    private function get_domains_count()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vaptsecure_domains';
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }

    private function get_features_count()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vaptsecure_feature_status';
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }

    private function get_active_enforcers()
    {
        $environment = $this->get_current_environment_profile();
        $enforcers = array();

        if (isset($environment['web_server']) && $environment['web_server']) {
            $enforcers[] = $environment['web_server'];
        }

        if (isset($environment['php_version']) && $environment['php_version']) {
            $enforcers[] = 'PHP';
        }

        if (isset($environment['database']) && $environment['database']) {
            $enforcers[] = 'Database';
        }

        return $enforcers;
    }
}